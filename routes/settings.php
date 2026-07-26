<?php

use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\ScanSettingsController;
use App\Http\Controllers\Settings\SecurityController;
use App\Http\Controllers\Settings\WorkerSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/scan', [ScanSettingsController::class, 'edit'])->name('scan.edit');
    Route::post('settings/scan', [ScanSettingsController::class, 'update'])->name('scan.update');

    Route::get('settings/workers', [WorkerSettingsController::class, 'edit'])->name('workers.edit');
    Route::post('settings/workers', [WorkerSettingsController::class, 'update'])->name('workers.update');

    Route::get('settings/security', [SecurityController::class, 'edit'])
        ->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::inertia('settings/appearance', 'settings/appearance')->name('appearance.edit');
});

Route::get('.well-known/passkey-endpoints', function () {
    return response()->json([
        'enroll' => route('security.edit'),
        'manage' => route('security.edit'),
    ]);
})->name('well-known.passkeys');
