<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DebugController;
use App\Http\Controllers\DirectoryController;
use App\Http\Controllers\ExecutionsController;
use App\Http\Controllers\JellyfinWebhookController;
use App\Http\Controllers\LibrariesController;
use App\Http\Controllers\WorkersController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! User::exists()) {
        return redirect()->to('/register');
    }

    if (Auth::check()) {
        return redirect()->to(route('dashboard'));
    }

    return inertia('welcome');
})->name('home');

Route::post('/webhooks/jellyfin', JellyfinWebhookController::class);

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('libraries/directories', DirectoryController::class)->name('libraries.directories');

    Route::controller(LibrariesController::class)->prefix('libraries')->name('libraries.')->group(function () {
        Route::post('{library}/scan', 'triggerScan')->name('scan');
        Route::post('{library}/toggle-job', 'toggleJob')->name('toggle-job');
        Route::post('{library}/toggle-worker', 'toggleWorker')->name('toggle-worker');
    });
    Route::resource('libraries', LibrariesController::class);

    Route::controller(ExecutionsController::class)->prefix('executions')->name('executions.')->group(function () {
        Route::post('batch/start', 'batchStart')->name('batch.start');
        Route::post('batch/pause', 'batchPause')->name('batch.pause');
        Route::post('batch/resume', 'batchResume')->name('batch.resume');
        Route::post('batch/stop', 'batchStop')->name('batch.stop');
        Route::post('batch/delete', 'batchDelete')->name('batch.delete');

        Route::post('{execution}/retry', 'retry')->name('retry');
        Route::post('{execution}/cancel', 'cancel')->name('cancel');
        Route::post('{execution}/start', 'start')->name('start');
        Route::post('{execution}/pause', 'pause')->name('pause');
        Route::post('{execution}/resume', 'resume')->name('resume');
        Route::post('{execution}/stop', 'stop')->name('stop');
    });
    Route::resource('executions', ExecutionsController::class)->only(['index', 'show', 'destroy']);

    if (app()->isLocal()) {
        Route::post('debug/restore-test-data', [DebugController::class, 'restoreTestData']);
    }

    Route::controller(WorkersController::class)->prefix('workers')->name('workers.')->group(function () {
        Route::post('start-all', 'startAll')->name('start-all');
        Route::post('pause-all', 'pauseAll')->name('pause-all');
        Route::post('resume-all', 'resumeAll')->name('resume-all');
        Route::post('stop-all', 'stopAll')->name('stop-all');

        Route::post('{worker}/start', 'start')->name('start');
        Route::post('{worker}/pause', 'pause')->name('pause');
        Route::post('{worker}/resume', 'resume')->name('resume');
        Route::post('{worker}/stop', 'stop')->name('stop');
    });
    Route::resource('workers', WorkersController::class)->only(['index', 'show', 'update']);
});

require __DIR__.'/settings.php';
require __DIR__.'/config.php';
