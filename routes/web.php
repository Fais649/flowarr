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

    Route::get('executions', [ExecutionsController::class, 'index'])->name('executions.index');
    Route::get('executions/{execution}', [ExecutionsController::class, 'show'])->name('executions.show');
    Route::post('executions/{execution}/retry', [ExecutionsController::class, 'retry'])->name('executions.retry');
    Route::post('executions/{execution}/cancel', [ExecutionsController::class, 'cancel'])->name('executions.cancel');

    if (app()->isLocal()) {
        Route::post('debug/restore-test-data', [DebugController::class, 'restoreTestData']);
    }

    Route::get('workers', [WorkersController::class, 'index'])->name('workers.index');
    Route::get('workers/{worker}', [WorkersController::class, 'show'])->name('workers.show');
});

require __DIR__.'/settings.php';
require __DIR__.'/config.php';
