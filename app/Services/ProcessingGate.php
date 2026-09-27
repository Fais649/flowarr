<?php

namespace App\Services;

use App\Models\Setting;
use App\Settings;
use Carbon\CarbonInterface;

/**
 * Decides whether media processing may run right now.
 *
 * Processing is held while it is paused manually, while a media server
 * reports active playback, or outside the configured processing window.
 * Running jobs are suspended (SIGSTOP) and resumed (SIGCONT) accordingly.
 */
class ProcessingGate
{
    public const REASON_MANUAL = 'manual';

    public const REASON_STREAMS = 'streams';

    public const REASON_SCHEDULE = 'schedule';

    public function __construct(private StreamTracker $streams) {}

    public function isOpen(): bool
    {
        return $this->pauseReasons() === [];
    }

    /**
     * @return list<string>
     */
    public function pauseReasons(): array
    {
        $reasons = [];

        if (Settings::isProcessingPaused()) {
            $reasons[] = self::REASON_MANUAL;
        }

        if ($this->streams->count() > 0) {
            $reasons[] = self::REASON_STREAMS;
        }

        if (! $this->isWithinWindow(now())) {
            $reasons[] = self::REASON_SCHEDULE;
        }

        return $reasons;
    }

    public function pause(): void
    {
        Setting::set(Settings::PROCESSING_PAUSED, '1');
    }

    public function resume(): void
    {
        Setting::set(Settings::PROCESSING_PAUSED, '0');
    }

    public function isWithinWindow(CarbonInterface $moment): bool
    {
        $window = Settings::processingWindow();

        if ($window === null) {
            return true;
        }

        $current = $moment->format('H:i');

        // A window like 22:00–06:00 wraps around midnight.
        if ($window['start'] < $window['end']) {
            return $current >= $window['start'] && $current < $window['end'];
        }

        return $current >= $window['start'] || $current < $window['end'];
    }

    /**
     * @return array{paused: bool, reasons: list<string>, manual: bool, active_streams: int, window: array{start: string, end: string}|null}
     */
    public function summary(): array
    {
        $reasons = $this->pauseReasons();

        return [
            'paused' => $reasons !== [],
            'reasons' => $reasons,
            'manual' => in_array(self::REASON_MANUAL, $reasons, true),
            'active_streams' => $this->streams->count(),
            'window' => Settings::processingWindow(),
        ];
    }
}
