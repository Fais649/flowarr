<?php

use App\Http\Controllers\Api\ExecutionsController;
use App\Http\Controllers\Api\LibrariesController;
use App\Http\Controllers\Api\StatusController;
use App\Http\Middleware\AuthenticateApiToken;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.')->middleware([AuthenticateApiToken::class, 'throttle:120,1'])->group(function () {
    Route::get('status', [StatusController::class, 'show'])->name('status');
    Route::post('processing/pause', [StatusController::class, 'pause'])->name('processing.pause');
    Route::post('processing/resume', [StatusController::class, 'resume'])->name('processing.resume');

    Route::get('libraries', [LibrariesController::class, 'index'])->name('libraries.index');
    Route::get('libraries/{library}', [LibrariesController::class, 'show'])->name('libraries.show');
    Route::post('libraries/{library}/scan', [LibrariesController::class, 'scan'])->name('libraries.scan');

    Route::get('executions', [ExecutionsController::class, 'index'])->name('executions.index');
    Route::get('executions/{execution}', [ExecutionsController::class, 'show'])->name('executions.show');
    Route::post('executions/{execution}/retry', [ExecutionsController::class, 'retry'])->name('executions.retry');
    Route::post('executions/{execution}/pause', [ExecutionsController::class, 'pause'])->name('executions.pause');
    Route::post('executions/{execution}/resume', [ExecutionsController::class, 'resume'])->name('executions.resume');
    Route::post('executions/{execution}/stop', [ExecutionsController::class, 'stop'])->name('executions.stop');
});
