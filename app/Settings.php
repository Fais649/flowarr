<?php

namespace App;

use App\Models\Setting;

/**
 * Typed accessors for user-editable settings stored in the settings table.
 */
class Settings
{
    public const SCAN_CONCURRENCY = 'scan.concurrency';

    public const PROCESSING_PAUSED = 'processing.paused';

    public const PROCESSING_WINDOW_START = 'processing.window_start';

    public const PROCESSING_WINDOW_END = 'processing.window_end';

    public const STREAM_TIMEOUT_MINUTES = 'processing.stream_timeout_minutes';

    public const NOTIFICATION_WEBHOOK_URL = 'notifications.webhook_url';

    public const NOTIFY_ON_COMPLETED = 'notifications.on_completed';

    public const NOTIFY_ON_FAILED = 'notifications.on_failed';

    public const API_TOKEN_HASH = 'api.token_hash';

    public static function scanConcurrency(): int
    {
        return (int) Setting::get(self::SCAN_CONCURRENCY, 2);
    }

    public static function isProcessingPaused(): bool
    {
        return (bool) Setting::get(self::PROCESSING_PAUSED, false);
    }

    /**
     * Daily time window (HH:MM, app timezone) in which media processing may run.
     * Null when processing is allowed around the clock.
     *
     * @return array{start: string, end: string}|null
     */
    public static function processingWindow(): ?array
    {
        $start = Setting::get(self::PROCESSING_WINDOW_START);
        $end = Setting::get(self::PROCESSING_WINDOW_END);

        if (blank($start) || blank($end) || $start === $end) {
            return null;
        }

        return ['start' => $start, 'end' => $end];
    }

    /**
     * Minutes after which a playback session without a stop event is
     * considered finished, so a missed webhook cannot pause processing forever.
     */
    public static function streamTimeoutMinutes(): int
    {
        return (int) Setting::get(self::STREAM_TIMEOUT_MINUTES, 240);
    }

    public static function notificationWebhookUrl(): ?string
    {
        $url = Setting::get(self::NOTIFICATION_WEBHOOK_URL);

        return blank($url) ? null : $url;
    }

    public static function notifyOnCompleted(): bool
    {
        return (bool) Setting::get(self::NOTIFY_ON_COMPLETED, false);
    }

    public static function notifyOnFailed(): bool
    {
        return (bool) Setting::get(self::NOTIFY_ON_FAILED, true);
    }

    public static function apiTokenHash(): ?string
    {
        $hash = Setting::get(self::API_TOKEN_HASH);

        return blank($hash) ? null : $hash;
    }

    /**
     * @return array<string, string|null>
     */
    public static function all(): array
    {
        return Setting::pluck('value', 'key')->toArray();
    }
}
