<?php

namespace App\Http\Controllers;

use App\Services\StreamTracker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

/**
 * Receives playback webhooks from Jellyfin, Plex and Emby and tracks active
 * streams so media processing pauses while someone is watching.
 */
class MediaServerWebhookController extends Controller
{
    private const START = 'start';

    private const STOP = 'stop';

    public function __construct(private StreamTracker $streams) {}

    public function jellyfin(Request $request): JsonResponse
    {
        return $this->handle($request, 'jellyfin', function (array $payload): array {
            // jellyfin-plugin-webhook: "NotificationType" in the default templates,
            // "Event" (playback.start/stop) in older/custom templates.
            $event = $payload['NotificationType'] ?? $payload['Event'] ?? null;

            $action = match ($event) {
                'PlaybackStart', 'playback.start' => self::START,
                'PlaybackStop', 'playback.stop' => self::STOP,
                // Progress events keep long sessions alive past the stream timeout.
                'PlaybackProgress', 'playback.progress' => ($payload['IsPaused'] ?? false) ? self::STOP : self::START,
                default => null,
            };

            $session = $payload['PlaySessionId'] ?? null;
            if ($session === null && isset($payload['DeviceId'])) {
                $session = $payload['DeviceId'].':'.($payload['ItemId'] ?? '');
            }

            return [$action, $session, $payload['Name'] ?? $payload['ItemName'] ?? null];
        });
    }

    public function plex(Request $request): JsonResponse
    {
        return $this->handle($request, 'plex', function (array $payload): array {
            $action = match ($payload['event'] ?? null) {
                'media.play', 'media.resume' => self::START,
                'media.pause', 'media.stop' => self::STOP,
                default => null,
            };

            $player = Arr::get($payload, 'Player.uuid');
            $session = $player !== null ? $player.':'.Arr::get($payload, 'Metadata.ratingKey', '') : null;

            return [$action, $session, Arr::get($payload, 'Metadata.title')];
        });
    }

    public function emby(Request $request): JsonResponse
    {
        return $this->handle($request, 'emby', function (array $payload): array {
            $action = match ($payload['Event'] ?? null) {
                'playback.start', 'playback.unpause' => self::START,
                'playback.stop', 'playback.pause' => self::STOP,
                default => null,
            };

            $session = Arr::get($payload, 'Session.Id')
                ?? Arr::get($payload, 'PlaybackInfo.PlaySessionId');

            return [$action, $session, Arr::get($payload, 'Item.Name')];
        });
    }

    /**
     * @param  callable(array<string, mixed>): array{0: string|null, 1: string|null, 2: string|null}  $parse
     */
    private function handle(Request $request, string $source, callable $parse): JsonResponse
    {
        if (! $this->isAuthorized($request)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        [$action, $session, $title] = $parse($this->payload($request));

        if ($action === self::START) {
            $this->streams->start($source, $session ?? 'unknown', $title);
        } elseif ($action === self::STOP) {
            $this->streams->stop($source, $session);
        }

        return response()->json([
            'status' => 'ok',
            'action' => $action ?? 'ignored',
            'active_streams' => $this->streams->count(),
        ]);
    }

    private function isAuthorized(Request $request): bool
    {
        $token = config('services.media_servers.webhook_token');

        if (blank($token)) {
            return true;
        }

        // Plex cannot send custom headers, so a ?token= query parameter is accepted too.
        $provided = $request->header('X-Flowarr-Token') ?? $request->query('token');

        return is_string($provided) && hash_equals((string) $token, $provided);
    }

    /**
     * Plex (and Emby when configured for form data) send multipart requests
     * with the JSON document in a "payload" or "data" field.
     *
     * @return array<string, mixed>
     */
    private function payload(Request $request): array
    {
        foreach (['payload', 'data'] as $field) {
            $raw = $request->input($field);

            if (is_string($raw) && ($decoded = json_decode($raw, true)) !== null && is_array($decoded)) {
                return $decoded;
            }
        }

        $json = $request->json()->all();

        return $json !== [] ? $json : $request->all();
    }
}
