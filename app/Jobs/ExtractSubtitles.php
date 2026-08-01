<?php

namespace App\Jobs;

use App\ExecutionStatus;
use App\MediaJobQueue;
use App\Services\ExtractSubtitlesExecutionService;
use Exception;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Support\Facades\Log;

#[Queue(queue: MediaJobQueue::EXTRACT_SUBTITLES)]
class ExtractSubtitles extends ExecutionJob
{
    public function handle(): void
    {
        try {
            app(ExtractSubtitlesExecutionService::class)->process($this->execution);
        } catch (Exception $e) {
            if ($this->execution->status !== ExecutionStatus::FAILED) {
                $this->execution->update(['status' => ExecutionStatus::FAILED, 'finished_at' => now()]);
            }
            Log::info(sprintf('Failed to process Execution %s: %s', $this->execution->id, $e), [$e->getCode(), $e->getTraceAsString()]);
        }
    }
}
