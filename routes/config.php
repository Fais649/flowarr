<?php

use App\Http\Controllers\Config\ScanSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('config')->name('config.')->group(function () {
    Route::get('/scan', [ScanSettingsController::class, 'edit'])->name('scan.edit');
    Route::post('/scan', [ScanSettingsController::class, 'update'])->name('scan.update');
});
