<?php

use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::forget('active_streams');
});

it('increments counter on playback.start', function () {
    $this->postJson('/webhooks/jellyfin', ['Event' => 'playback.start'])
        ->assertOk()
        ->assertJson(['status' => 'ok']);

    expect(Cache::get('active_streams'))->toBe(1);
});

it('decrements counter on playback.stop', function () {
    Cache::forever('active_streams', 3);

    $this->postJson('/webhooks/jellyfin', ['Event' => 'playback.stop'])
        ->assertOk();

    expect(Cache::get('active_streams'))->toBe(2);
});

it('does not decrement below zero', function () {
    Cache::forever('active_streams', 0);

    $this->postJson('/webhooks/jellyfin', ['Event' => 'playback.stop'])
        ->assertOk();

    expect(Cache::get('active_streams'))->toBe(0);
});

it('returns 401 with invalid token', function () {
    config(['services.jellyfin.webhook_token' => 'secret123']);

    $this->postJson('/webhooks/jellyfin', ['Event' => 'playback.start'])
        ->assertUnauthorized();

    expect(Cache::get('active_streams'))->toBeNull();
});

it('accepts valid token', function () {
    config(['services.jellyfin.webhook_token' => 'secret123']);

    $this->postJson('/webhooks/jellyfin', ['Event' => 'playback.start'], ['X-Flowarr-Token' => 'secret123'])
        ->assertOk();

    expect(Cache::get('active_streams'))->toBe(1);
});

it('ignores unknown event types', function () {
    $this->postJson('/webhooks/jellyfin', ['Event' => 'playback.pause'])
        ->assertOk();

    expect(Cache::get('active_streams'))->toBeNull();
});

it('handles missing event key gracefully', function () {
    $this->postJson('/webhooks/jellyfin', [])
        ->assertOk();

    expect(Cache::get('active_streams'))->toBeNull();
});
