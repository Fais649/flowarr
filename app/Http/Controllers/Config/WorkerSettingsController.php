<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateWorkerSettingsRequest;
use App\Models\Setting;
use App\Settings;
use Inertia\Inertia;
use Inertia\Response;

class WorkerSettingsController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('config/workers', [
            'concurrency' => [
                'transcode_media' => Settings::concurrency('transcode_media'),
                'extract_subs' => Settings::concurrency('extract_subs'),
                'convert_sub' => Settings::concurrency('convert_sub'),
            ],
            'paused' => Settings::isPaused(),
        ]);
    }

    public function update(UpdateWorkerSettingsRequest $request)
    {
        foreach ($request->validated('concurrency') as $key => $value) {
            Setting::set("concurrency.{$key}", (string) $value);
        }

        Setting::set('media_processing_paused', $request->validated('paused') ? '1' : '0');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Worker settings updated.')]);

        return to_route('config.workers.edit');
    }
}
