<?php

use App\Models\Setting;
use App\Services\StreamTracker;
use App\Settings;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

function streams(): StreamTracker
{
    return app(StreamTracker::class);
}

it('tracks jellyfin playback sessions from the default webhook template', function () {
    $this->postJson('/webhooks/jellyfin', ['NotificationType' => 'PlaybackStart', 'PlaySessionId' => 's1', 'Name' => 'Alien'])
        ->assertOk()
        ->assertJson(['status' => 'ok', 'action' => 'start', 'active_streams' => 1]);

    $this->postJson('/webhooks/jellyfin', ['NotificationType' => 'PlaybackStart', 'PlaySessionId' => 's2'])->assertOk();
    expect(streams()->count())->toBe(2);

    $this->postJson('/webhooks/jellyfin', ['NotificationType' => 'PlaybackStop', 'PlaySessionId' => 's1'])
        ->assertJson(['action' => 'stop', 'active_streams' => 1]);

    expect(array_column(streams()->sessions(), 'title'))->toBe([null]);
});

it('is idempotent for repeated start events of the same session', function () {
    foreach (range(1, 3) as $_) {
        $this->postJson('/webhooks/jellyfin', ['NotificationType' => 'PlaybackStart', 'PlaySessionId' => 's1']);
    }

    expect(streams()->count())->toBe(1);
});

it('supports the legacy Event field', function () {
    $this->postJson('/webhooks/jellyfin', ['Event' => 'playback.start'])->assertOk();
    expect(streams()->count())->toBe(1);

    $this->postJson('/webhooks/jellyfin', ['Event' => 'playback.stop'])->assertOk();
    expect(streams()->count())->toBe(0);
});

it('does not go below zero when stop arrives without a start', function () {
    $this->postJson('/webhooks/jellyfin', ['NotificationType' => 'PlaybackStop', 'PlaySessionId' => 'x'])->assertOk();

    expect(streams()->count())->toBe(0);
});

it('treats paused jellyfin progress events as a stopped stream', function () {
    $this->postJson('/webhooks/jellyfin', ['NotificationType' => 'PlaybackStart', 'PlaySessionId' => 's1']);
    $this->postJson('/webhooks/jellyfin', ['NotificationType' => 'PlaybackProgress', 'PlaySessionId' => 's1', 'IsPaused' => true]);

    expect(streams()->count())->toBe(0);
});

it('ignores unknown events and empty payloads', function () {
    $this->postJson('/webhooks/jellyfin', ['NotificationType' => 'ItemAdded'])->assertJson(['action' => 'ignored']);
    $this->postJson('/webhooks/jellyfin', [])->assertOk();

    expect(streams()->count())->toBe(0);
});

it('parses plex multipart payloads', function () {
    $payload = fn (string $event) => ['payload' => json_encode([
        'event' => $event,
        'Player' => ['uuid' => 'player-1'],
        'Metadata' => ['ratingKey' => '42', 'title' => 'Heat'],
    ])];

    $this->post('/webhooks/plex', $payload('media.play'))->assertOk();
    expect(array_values(streams()->sessions())[0]['title'])->toBe('Heat');

    $this->post('/webhooks/plex', $payload('media.stop'))->assertOk();
    expect(streams()->count())->toBe(0);
});

it('parses emby webhooks', function () {
    $this->postJson('/webhooks/emby', ['Event' => 'playback.start', 'Session' => ['Id' => 'e1'], 'Item' => ['Name' => 'Ran']])->assertOk();
    expect(streams()->count())->toBe(1);

    $this->postJson('/webhooks/emby', ['Event' => 'playback.pause', 'Session' => ['Id' => 'e1']])->assertOk();
    expect(streams()->count())->toBe(0);
});

it('requires the configured token via header or query string', function () {
    config(['services.media_servers.webhook_token' => 'secret123']);

    $this->postJson('/webhooks/jellyfin', ['Event' => 'playback.start'])->assertUnauthorized();
    $this->postJson('/webhooks/jellyfin', ['Event' => 'playback.start'], ['X-Flowarr-Token' => 'wrong'])->assertUnauthorized();
    expect(streams()->count())->toBe(0);

    $this->postJson('/webhooks/jellyfin', ['Event' => 'playback.start'], ['X-Flowarr-Token' => 'secret123'])->assertOk();
    $this->post('/webhooks/plex?token=secret123', ['payload' => json_encode(['event' => 'media.play'])])->assertOk();

    expect(streams()->count())->toBe(2);
});

it('forgets sessions that never sent a stop event after the stream timeout', function () {
    Setting::set(Settings::STREAM_TIMEOUT_MINUTES, '30');

    $this->postJson('/webhooks/jellyfin', ['NotificationType' => 'PlaybackStart', 'PlaySessionId' => 's1']);
    expect(streams()->count())->toBe(1);

    $this->travel(31)->minutes();

    expect(streams()->count())->toBe(0);
});

it('is exempt from csrf verification', function () {
    $except = (new ReflectionClass(PreventRequestForgery::class))->getStaticPropertyValue('neverVerify');

    expect($except)->toContain('webhooks/*');
});
