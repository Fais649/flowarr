<?php

use App\Models\Setting;
use App\Settings;

it('returns default scan concurrency of 2 when not configured', function () {
    expect(Settings::scanConcurrency())->toBe(2);
});

it('returns configured scan concurrency', function () {
    Setting::set('scan.concurrency', '5');

    expect(Settings::scanConcurrency())->toBe(5);
});
