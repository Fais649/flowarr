<?php

use App\Models\User;
use App\Settings;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

function validProcessingSettings(array $overrides = []): array
{
    return [
        'window_enabled' => true,
        'window_start' => '23:00',
        'window_end' => '07:00',
        'stream_timeout_minutes' => 120,
        'webhook_url' => 'https://discord.com/api/webhooks/1/abc',
        'notify_on_completed' => true,
        'notify_on_failed' => false,
        ...$overrides,
    ];
}

it('shows the processing settings page', function () {
    $this->get('/config/processing')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('config/processing')
            ->where('settings.window_enabled', false)
            ->where('settings.stream_timeout_minutes', 240)
            ->where('settings.notify_on_failed', true)
            ->where('hasApiToken', false)
            ->has('webhookUrls.plex'));
});

it('updates processing settings', function () {
    $this->post('/config/processing', validProcessingSettings())
        ->assertRedirect('/config/processing');

    expect(Settings::processingWindow())->toBe(['start' => '23:00', 'end' => '07:00'])
        ->and(Settings::streamTimeoutMinutes())->toBe(120)
        ->and(Settings::notificationWebhookUrl())->toBe('https://discord.com/api/webhooks/1/abc')
        ->and(Settings::notifyOnCompleted())->toBeTrue()
        ->and(Settings::notifyOnFailed())->toBeFalse();
});

it('clears the processing window when disabled', function () {
    $this->post('/config/processing', validProcessingSettings());
    $this->post('/config/processing', validProcessingSettings(['window_enabled' => false, 'webhook_url' => '']));

    expect(Settings::processingWindow())->toBeNull()
        ->and(Settings::notificationWebhookUrl())->toBeNull();
});

it('validates processing settings', function () {
    $this->post('/config/processing', validProcessingSettings([
        'window_start' => '25:00',
        'stream_timeout_minutes' => 1,
        'webhook_url' => 'ftp://example.com',
    ]))->assertSessionHasErrors(['window_start', 'stream_timeout_minutes', 'webhook_url']);

    $this->post('/config/processing', validProcessingSettings(['window_start' => '07:00', 'window_end' => '07:00']))
        ->assertSessionHasErrors('window_start');
});

it('generates and revokes the api token', function () {
    $this->post('/config/processing/api-token')
        ->assertRedirect()
        ->assertInertiaFlash('apiToken');

    expect(Settings::apiTokenHash())->not->toBeNull();

    $this->delete('/config/processing/api-token')->assertRedirect();

    expect(Settings::apiTokenHash())->toBeNull();
});

it('requires authentication', function () {
    auth()->logout();

    $this->get('/config/processing')->assertRedirect('/login');
});
