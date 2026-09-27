<?php

namespace App\Services;

use App\ExecutionStatus;
use App\Jobs\SendExecutionNotification;
use App\Models\Execution;
use App\Settings;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Sends post-processing notifications to a user-configured webhook.
 *
 * The JSON payload carries the message under "text" (Slack, Mattermost,
 * Gotify-style bridges), "content" (Discord) and "message", plus a structured
 * "execution" object for custom automation.
 */
class ExecutionNotifier
{
    public function executionFinished(Execution $execution): void
    {
        if ($this->shouldNotify($execution)) {
            SendExecutionNotification::dispatch($execution->id);
        }
    }

    public function shouldNotify(Execution $execution): bool
    {
        if (Settings::notificationWebhookUrl() === null) {
            return false;
        }

        return match ($execution->status) {
            ExecutionStatus::COMPLETED => Settings::notifyOnCompleted(),
            ExecutionStatus::FAILED => Settings::notifyOnFailed(),
            default => false,
        };
    }

    public function send(Execution $execution): Response
    {
        $execution->loadMissing('libraryJob.library');

        return $this->post($this->payload($execution));
    }

    public function sendTest(): Response
    {
        $text = 'Flowarr test notification: webhook notifications are configured correctly.';

        return $this->post([
            'event' => 'test',
            'text' => $text,
            'content' => $text,
            'message' => $text,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(Execution $execution): array
    {
        $jobType = $execution->libraryJob?->job_id;
        $label = $jobType?->label() ?? 'Job';
        $fileName = basename($execution->file_path);

        $text = match ($execution->status) {
            ExecutionStatus::COMPLETED => "✅ {$label} completed: {$fileName}",
            ExecutionStatus::FAILED => "❌ {$label} failed: {$fileName}".($execution->message ? " — {$execution->message}" : ''),
            default => "{$label} {$execution->status->value}: {$fileName}",
        };

        return [
            'event' => 'execution.'.$execution->status->value,
            'text' => $text,
            'content' => $text,
            'message' => $text,
            'execution' => [
                'id' => $execution->id,
                'status' => $execution->status->value,
                'job_type' => $jobType?->value,
                'file_path' => $execution->file_path,
                'library' => $execution->libraryJob?->library?->base_path,
                'message' => $execution->message,
                'started_at' => $execution->started_at?->toIso8601String(),
                'finished_at' => $execution->finished_at?->toIso8601String(),
                'duration_seconds' => $execution->durationSeconds(),
                'url' => url("/executions/{$execution->id}"),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function post(array $payload): Response
    {
        $url = Settings::notificationWebhookUrl();

        if ($url === null) {
            throw new \RuntimeException('No notification webhook URL is configured.');
        }

        return Http::timeout(10)
            ->acceptJson()
            ->withUserAgent('Flowarr')
            ->post($url, $payload)
            ->throw();
    }
}
