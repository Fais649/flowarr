<?php

namespace App\Http\Controllers;

use App\MediaJobQueue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class PlaybackController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $event = $request->input('NotificationType');
        $queueName = 'default';

        switch ($event) {
            case 'PlaybackStart':
                Log::info('Jellyfin playback started. Pausing queue.');
                foreach (MediaJobQueue::cases() as $jobQueue) {
                    Queue::pause($jobQueue->value, $queueName);
                }
                Cache::forever('media_processing_paused', true);
                break;

            case 'PlaybackStop':
                Log::info('Jellyfin playback stopped. Resuming queue.');
                foreach (MediaJobQueue::cases() as $jobQueue) {
                    Queue::resume($jobQueue->value, $queueName);
                }
                Cache::forget('media_processing_paused');
                break;
        }

        return response()->json(['status' => 'received'], 200);
    }
}
