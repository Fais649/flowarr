<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DebugController;
use App\Http\Controllers\DirectoryController;
use App\Http\Controllers\ExecutionsController;
use App\Http\Controllers\JellyfinWebhookController;
use App\Http\Controllers\LibrariesController;
use App\Http\Controllers\WorkersController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! User::exists()) {
        return redirect()->to('/register');
    }

    if (auth()->check()) {
        return redirect()->to(route('dashboard'));
    }

    return inertia('welcome');
})->name('home');

Route::post('/webhooks/jellyfin', JellyfinWebhookController::class);

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('libraries/directories', DirectoryController::class)->name('libraries.directories');

    Route::get('libraries', [LibrariesController::class, 'index'])->name('libraries.index');
    Route::get('libraries/create', [LibrariesController::class, 'create'])->name('libraries.create');
    Route::post('libraries', [LibrariesController::class, 'store'])->name('libraries.store');
    Route::get('libraries/{library}', [LibrariesController::class, 'show'])->name('libraries.show');
    Route::get('libraries/{library}/edit', [LibrariesController::class, 'edit'])->name('libraries.edit');
    Route::patch('libraries/{library}', [LibrariesController::class, 'update'])->name('libraries.update');
    Route::delete('libraries/{library}', [LibrariesController::class, 'destroy'])->name('libraries.destroy');
    Route::post('libraries/{library}/scan', [LibrariesController::class, 'triggerScan'])->name('libraries.scan');
    Route::post('libraries/{library}/toggle-job', [LibrariesController::class, 'toggleJob'])->name('libraries.toggle-job');
    Route::post('libraries/{library}/toggle-worker', [LibrariesController::class, 'toggleWorker'])->name('libraries.toggle-worker');

    Route::get('executions', [ExecutionsController::class, 'index'])->name('executions.index');
    Route::get('executions/{execution}', [ExecutionsController::class, 'show'])->name('executions.show');
    Route::post('executions/batch/start', [ExecutionsController::class, 'batchStart'])->name('executions.batch.start');
    Route::post('executions/batch/pause', [ExecutionsController::class, 'batchPause'])->name('executions.batch.pause');
    Route::post('executions/batch/resume', [ExecutionsController::class, 'batchResume'])->name('executions.batch.resume');
    Route::post('executions/batch/stop', [ExecutionsController::class, 'batchStop'])->name('executions.batch.stop');
    Route::post('executions/batch/delete', [ExecutionsController::class, 'batchDelete'])->name('executions.batch.delete');
    Route::post('executions/{execution}/retry', [ExecutionsController::class, 'retry'])->name('executions.retry');
    Route::post('executions/{execution}/cancel', [ExecutionsController::class, 'cancel'])->name('executions.cancel');
    Route::post('executions/{execution}/start', [ExecutionsController::class, 'start'])->name('executions.start');
    Route::post('executions/{execution}/pause', [ExecutionsController::class, 'pause'])->name('executions.pause');
    Route::post('executions/{execution}/resume', [ExecutionsController::class, 'resume'])->name('executions.resume');
    Route::post('executions/{execution}/stop', [ExecutionsController::class, 'stop'])->name('executions.stop');
    Route::delete('executions/{execution}', [ExecutionsController::class, 'destroy'])->name('executions.destroy');

    if (app()->isLocal()) {
        Route::post('debug/restore-test-data', [DebugController::class, 'restoreTestData']);
    }

    Route::get('workers', [WorkersController::class, 'index'])->name('workers.index');
    Route::get('workers/{worker}', [WorkersController::class, 'show'])->name('workers.show');
    Route::patch('workers/{worker}', [WorkersController::class, 'update'])->name('workers.update');
    Route::post('workers/start-all', [WorkersController::class, 'startAll'])->name('workers.start-all');
    Route::post('workers/pause-all', [WorkersController::class, 'pauseAll'])->name('workers.pause-all');
    Route::post('workers/resume-all', [WorkersController::class, 'resumeAll'])->name('workers.resume-all');
    Route::post('workers/stop-all', [WorkersController::class, 'stopAll'])->name('workers.stop-all');
    Route::post('workers/{worker}/start', [WorkersController::class, 'start'])->name('workers.start');
    Route::post('workers/{worker}/pause', [WorkersController::class, 'pause'])->name('workers.pause');
    Route::post('workers/{worker}/resume', [WorkersController::class, 'resume'])->name('workers.resume');
    Route::post('workers/{worker}/stop', [WorkersController::class, 'stop'])->name('workers.stop');
});

require __DIR__.'/settings.php';
require __DIR__.'/config.php';
