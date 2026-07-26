<?php

use App\Models\User;

test('guests are redirected to the onboarding wizard when no users exist', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect('/register');
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});
