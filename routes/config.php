<?php

use App\Http\Controllers\Config\ScanSettingsController;
use App\Http\Controllers\Config\WorkerSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('config')->name('config.')->group(function () {
    Route::get('/scan', [ScanSettingsController::class, 'edit'])->name('scan.edit');
    Route::post('/scan', [ScanSettingsController::class, 'update'])->name('scan.update');

    Route::get('/workers', [WorkerSettingsController::class, 'edit'])->name('workers.edit');
    Route::post('/workers', [WorkerSettingsController::class, 'update'])->name('workers.update');
});
