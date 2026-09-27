<?php

namespace App\Services;

use App\Settings;
use Illuminate\Support\Facades\Cache;

/**
 * Tracks active playback sessions reported by media server webhooks.
 *
 * Sessions are keyed so duplicate or out-of-order events stay idempotent,
 * and entries expire after the configured timeout so a missed "stop" event
 * cannot pause processing forever.
 */
class StreamTracker
{
    private const CACHE_KEY = 'media_server.active_streams';

    public function start(string $source, string $sessionKey, ?string $title = null): void
    {
        $this->mutate(function (array $sessions) use ($source, $sessionKey, $title): array {
            $key = "{$source}:{$sessionKey}";
            $sessions[$key] = [
                'source' => $source,
                'title' => $title ?? $sessions[$key]['title'] ?? null,
                'started_at' => $sessions[$key]['started_at'] ?? now()->getTimestamp(),
                'seen_at' => now()->getTimestamp(),
            ];

            return $sessions;
        });
    }

    public function stop(string $source, ?string $sessionKey): void
    {
        $this->mutate(function (array $sessions) use ($source, $sessionKey): array {
            if ($sessionKey !== null && isset($sessions["{$source}:{$sessionKey}"])) {
                unset($sessions["{$source}:{$sessionKey}"]);

                return $sessions;
            }

            // Without a usable session id, end the oldest session from this server.
            $fromSource = array_filter($sessions, fn (array $s) => $s['source'] === $source);
            uasort($fromSource, fn (array $a, array $b) => $a['started_at'] <=> $b['started_at']);
            $oldest = array_key_first($fromSource);

            if ($oldest !== null) {
                unset($sessions[$oldest]);
            }

            return $sessions;
        });
    }

    public function clear(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function count(): int
    {
        return count($this->sessions());
    }

    /**
     * @return array<string, array{source: string, title: string|null, started_at: int, seen_at: int}>
     */
    public function sessions(): array
    {
        return $this->prune(Cache::get(self::CACHE_KEY, []));
    }

    /**
     * @param  callable(array<string, array{source: string, title: string|null, started_at: int, seen_at: int}>): array<string, array{source: string, title: string|null, started_at: int, seen_at: int}>  $callback
     */
    private function mutate(callable $callback): void
    {
        Cache::lock(self::CACHE_KEY.':lock', 5)->block(5, function () use ($callback): void {
            $sessions = $callback($this->prune(Cache::get(self::CACHE_KEY, [])));
            Cache::forever(self::CACHE_KEY, $sessions);
        });
    }

    /**
     * @param  array<string, array{source: string, title: string|null, started_at: int, seen_at: int}>  $sessions
     * @return array<string, array{source: string, title: string|null, started_at: int, seen_at: int}>
     */
    private function prune(array $sessions): array
    {
        $cutoff = now()->getTimestamp() - Settings::streamTimeoutMinutes() * 60;

        return array_filter($sessions, fn (array $s) => $s['seen_at'] >= $cutoff);
    }
}
