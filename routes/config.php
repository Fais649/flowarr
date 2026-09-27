<?php

use App\Http\Controllers\Config\ProcessingSettingsController;
use App\Http\Controllers\Config\ScanSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('config')->name('config.')->group(function () {
    Route::get('/scan', [ScanSettingsController::class, 'edit'])->name('scan.edit');
    Route::post('/scan', [ScanSettingsController::class, 'update'])->name('scan.update');

    Route::controller(ProcessingSettingsController::class)->prefix('processing')->name('processing.')->group(function () {
        Route::get('/', 'edit')->name('edit');
        Route::post('/', 'update')->name('update');
        Route::post('/test-notification', 'testNotification')->name('test-notification');
        Route::post('/api-token', 'generateApiToken')->name('api-token.generate');
        Route::delete('/api-token', 'revokeApiToken')->name('api-token.revoke');
    });
});
