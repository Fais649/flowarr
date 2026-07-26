<?php

use App\Models\User;

test('welcome page redirects to onboarding when no users exist', function () {
    $response = $this->get(route('home'));

    $response->assertRedirect('/register');
});

test('welcome page is accessible when users exist', function () {
    User::factory()->create();

    $response = $this->get(route('home'));

    $response->assertOk();
});
