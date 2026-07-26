<?php

namespace App;

use App\Models\Setting;

class Settings
{
    public static function concurrency(string $jobType): int
    {
        return (int) Setting::get("concurrency.{$jobType}", 1);
    }

    public static function isPaused(): bool
    {
        return (bool) Setting::get('media_processing_paused', false);
    }

    public static function scanConcurrency(): int
    {
        return (int) Setting::get('scan.concurrency', 2);
    }

    public static function all(): array
    {
        return Setting::pluck('value', 'key')->toArray();
    }
}
