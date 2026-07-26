<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

test('visitors are redirected to the onboarding wizard when no users exist', function () {
    $response = $this->get('/dashboard');

    $response->assertRedirect('/register');
});

test('login page is accessible when no users exist', function () {
    $response = $this->get('/login');

    $response->assertOk();
});

test('admin recovery command deletes all users', function () {
    $user = User::factory()->create();
    DB::table('passkeys')->insert([
        ['user_id' => $user->id, 'name' => 'test', 'credential_id' => 'abc', 'credential' => '{}'],
    ]);

    $this->artisan('app:admin-recover', ['--force' => true])
        ->assertSuccessful();

    expect(User::count())->toBe(0);
    expect(DB::table('passkeys')->count())->toBe(0);
});

test('admin recovery command prompts for confirmation without force flag', function () {
    User::factory()->create();

    $this->artisan('app:admin-recover')
        ->expectsConfirmation('This will delete ALL users and passkeys. Are you sure?', 'no')
        ->assertSuccessful();

    expect(User::count())->toBe(1);
});
