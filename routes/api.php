<?php

use App\Http\Controllers\PlaybackController;
use Illuminate\Support\Facades\Route;

Route::post('/playback', [PlaybackController::class, 'handle']);
