<?php

use App\Models\User;
use App\Settings;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('shows the scan settings page', function () {
    $this->get('/config/scan')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('config/scan')->where('concurrency', 2));
});

it('updates scan concurrency', function () {
    $this->post('/config/scan', ['concurrency' => 4])->assertRedirect('/config/scan');

    expect(Settings::scanConcurrency())->toBe(4);
});

it('validates scan concurrency', function () {
    $this->post('/config/scan', ['concurrency' => 0])->assertSessionHasErrors('concurrency');
});
