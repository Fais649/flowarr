<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProcessingSettingsRequest;
use App\Models\Setting;
use App\Services\ExecutionNotifier;
use App\Services\ProcessingGate;
use App\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class ProcessingSettingsController extends Controller
{
    public function edit(ProcessingGate $gate): Response
    {
        $window = Settings::processingWindow();

        return Inertia::render('config/processing', [
            'settings' => [
                'window_enabled' => $window !== null,
                'window_start' => $window['start'] ?? '22:00',
                'window_end' => $window['end'] ?? '06:00',
                'stream_timeout_minutes' => Settings::streamTimeoutMinutes(),
                'webhook_url' => Settings::notificationWebhookUrl() ?? '',
                'notify_on_completed' => Settings::notifyOnCompleted(),
                'notify_on_failed' => Settings::notifyOnFailed(),
            ],
            'processing' => $gate->summary(),
            'timezone' => config('app.timezone'),
            'hasApiToken' => Settings::apiTokenHash() !== null,
            'webhookUrls' => [
                'jellyfin' => url('/webhooks/jellyfin'),
                'plex' => url('/webhooks/plex'),
                'emby' => url('/webhooks/emby'),
            ],
            'webhookTokenConfigured' => filled(config('services.media_servers.webhook_token')),
        ]);
    }

    public function update(UpdateProcessingSettingsRequest $request): RedirectResponse
    {
        $windowEnabled = $request->boolean('window_enabled');

        Setting::set(Settings::PROCESSING_WINDOW_START, $windowEnabled ? $request->validated('window_start') : null);
        Setting::set(Settings::PROCESSING_WINDOW_END, $windowEnabled ? $request->validated('window_end') : null);
        Setting::set(Settings::STREAM_TIMEOUT_MINUTES, (string) $request->validated('stream_timeout_minutes'));
        Setting::set(Settings::NOTIFICATION_WEBHOOK_URL, $request->validated('webhook_url') ?: null);
        Setting::set(Settings::NOTIFY_ON_COMPLETED, $request->boolean('notify_on_completed') ? '1' : '0');
        Setting::set(Settings::NOTIFY_ON_FAILED, $request->boolean('notify_on_failed') ? '1' : '0');

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Processing settings updated.']);

        return to_route('config.processing.edit');
    }

    public function testNotification(ExecutionNotifier $notifier): RedirectResponse
    {
        try {
            $notifier->sendTest();
            Inertia::flash('toast', ['type' => 'success', 'message' => 'Test notification sent.']);
        } catch (Throwable $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Test notification failed: '.Str::limit($e->getMessage(), 200)]);
        }

        return back();
    }

    public function generateApiToken(): RedirectResponse
    {
        $token = 'flw_'.Str::random(40);

        Setting::set(Settings::API_TOKEN_HASH, hash('sha256', $token));

        // Only shown once; just the hash is stored.
        Inertia::flash('apiToken', $token);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'API token generated. Copy it now, it will not be shown again.']);

        return back();
    }

    public function revokeApiToken(): RedirectResponse
    {
        Setting::forget(Settings::API_TOKEN_HASH);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'API token revoked.']);

        return back();
    }
}
