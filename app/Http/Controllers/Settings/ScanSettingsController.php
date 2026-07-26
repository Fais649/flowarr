<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateScanSettingsRequest;
use App\Models\Setting;
use App\Settings;
use Inertia\Inertia;
use Inertia\Response;

class ScanSettingsController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('settings/scan', [
            'concurrency' => Settings::scanConcurrency(),
        ]);
    }

    public function update(UpdateScanSettingsRequest $request)
    {
        Setting::set('scan.concurrency', (string) $request->validated('concurrency'));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Scan settings updated.']);

        return to_route('scan.edit');
    }
}
