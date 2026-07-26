<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class JellyfinWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $token = config('services.jellyfin.webhook_token');

        if ($token) {
            $header = $request->header('X-Flowarr-Token');

            if ($header !== $token) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }

        $eventType = $request->input('Event');

        if ($eventType === 'playback.start') {
            Cache::increment('active_streams');
        } elseif ($eventType === 'playback.stop') {
            $current = (int) Cache::get('active_streams', 0);
            Cache::forever('active_streams', max(0, $current - 1));
        }

        return response()->json(['status' => 'ok']);
    }
}
