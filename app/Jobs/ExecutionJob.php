<?php

namespace App\Jobs;

use App\ExecutionStatus;
use App\Models\Execution;
use App\Services\ExecutionNotifier;
use App\Services\ProcessExecutionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

abstract class ExecutionJob implements ShouldQueue
{
    use Queueable;

    /**
     * Media jobs can legitimately run for hours, so the worker must not kill them.
     */
    public int $timeout = 0;

    /**
     * Failures are recorded on the execution and retried from the UI/API
     * rather than silently re-running a partially processed file.
     */
    public int $tries = 1;

    /**
     * Executions deleted while queued are simply dropped.
     */
    public bool $deleteWhenMissingModels = true;

    public function __construct(public Execution $execution) {}

    /**
     * @return class-string<ProcessExecutionService>
     */
    abstract protected function service(): string;

    public function handle(): void
    {
        $execution = $this->execution->fresh(['worker', 'libraryJob.library.workers']);

        // Stopped or already handled while waiting in the queue.
        if ($execution === null || $execution->status !== ExecutionStatus::QUEUED) {
            return;
        }

        app($this->service())->process($execution);
    }

    public function failed(?Throwable $exception): void
    {
        $execution = $this->execution->fresh();

        if ($execution === null || $execution->status->isFinished()) {
            return;
        }

        $execution->update([
            'status' => ExecutionStatus::FAILED,
            'finished_at' => now(),
            'message' => $exception?->getMessage() ?? 'Job failed',
        ]);

        Log::error(sprintf('Execution %d failed: %s', $execution->id, $exception?->getMessage()));

        app(ExecutionNotifier::class)->executionFinished($execution);
    }
}
