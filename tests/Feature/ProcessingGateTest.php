<?php

use App\Models\Setting;
use App\Services\ProcessingGate;
use App\Services\StreamTracker;
use App\Settings;
use Illuminate\Support\Carbon;

function gate(): ProcessingGate
{
    return app(ProcessingGate::class);
}

function setWindow(string $start, string $end): void
{
    Setting::set(Settings::PROCESSING_WINDOW_START, $start);
    Setting::set(Settings::PROCESSING_WINDOW_END, $end);
}

it('is open by default', function () {
    expect(gate()->isOpen())->toBeTrue()
        ->and(gate()->summary())->toMatchArray(['paused' => false, 'reasons' => [], 'active_streams' => 0, 'window' => null]);
});

it('closes while paused manually', function () {
    gate()->pause();
    expect(gate()->pauseReasons())->toBe([ProcessingGate::REASON_MANUAL]);

    gate()->resume();
    expect(gate()->isOpen())->toBeTrue();
});

it('closes while media is streamed', function () {
    app(StreamTracker::class)->start('jellyfin', 'session');

    expect(gate()->pauseReasons())->toBe([ProcessingGate::REASON_STREAMS])
        ->and(gate()->summary()['active_streams'])->toBe(1);
});

it('only opens inside the processing window', function () {
    setWindow('01:00', '07:00');

    expect(gate()->isWithinWindow(Carbon::parse('2026-01-01 03:00')))->toBeTrue()
        ->and(gate()->isWithinWindow(Carbon::parse('2026-01-01 07:00')))->toBeFalse()
        ->and(gate()->isWithinWindow(Carbon::parse('2026-01-01 12:00')))->toBeFalse();

    $this->travelTo(Carbon::parse('2026-01-01 12:00'));
    expect(gate()->pauseReasons())->toBe([ProcessingGate::REASON_SCHEDULE]);
});

it('supports windows that wrap around midnight', function () {
    setWindow('22:00', '06:00');

    expect(gate()->isWithinWindow(Carbon::parse('2026-01-01 23:30')))->toBeTrue()
        ->and(gate()->isWithinWindow(Carbon::parse('2026-01-01 05:59')))->toBeTrue()
        ->and(gate()->isWithinWindow(Carbon::parse('2026-01-01 06:00')))->toBeFalse()
        ->and(gate()->isWithinWindow(Carbon::parse('2026-01-01 14:00')))->toBeFalse();
});
