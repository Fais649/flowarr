<?php

namespace App\Services;

use App\ExecutionStatus;
use App\Models\Execution;
use Illuminate\Database\Eloquent\Builder;

/**
 * Lifecycle actions on executions.
 *
 * Only the database status is changed here; the queue worker running the
 * execution polls its status and suspends, resumes or terminates the actual
 * process (see ProcessExecutionService::applyControlState()).
 */
class ExecutionControl
{
    public function pause(Execution $execution): bool
    {
        return $this->pauseMany(Execution::whereKey($execution->id)) > 0;
    }

    public function resume(Execution $execution): bool
    {
        return $this->resumeMany(Execution::whereKey($execution->id)) > 0;
    }

    public function stop(Execution $execution): bool
    {
        return $this->stopMany(Execution::whereKey($execution->id)) > 0;
    }

    public function retry(Execution $execution): bool
    {
        return $this->retryMany(Execution::whereKey($execution->id)) > 0;
    }

    /**
     * "Start" gets an execution moving again: resume it when paused, re-queue
     * it when it was stopped or failed.
     */
    public function start(Execution $execution): bool
    {
        return $this->startMany(Execution::whereKey($execution->id)) > 0;
    }

    /**
     * @param  Builder<Execution>  $query
     */
    public function pauseMany(Builder $query): int
    {
        return (clone $query)->where('status', ExecutionStatus::PROCESSING)
            ->update(['status' => ExecutionStatus::PAUSED, 'message' => 'Paused by user']);
    }

    /**
     * @param  Builder<Execution>  $query
     */
    public function resumeMany(Builder $query): int
    {
        return (clone $query)->where('status', ExecutionStatus::PAUSED)
            ->update(['status' => ExecutionStatus::PROCESSING, 'message' => null]);
    }

    /**
     * @param  Builder<Execution>  $query
     */
    public function stopMany(Builder $query): int
    {
        return (clone $query)->whereIn('status', ExecutionStatus::active())
            ->update(['status' => ExecutionStatus::STOPPED, 'message' => 'Stopped by user']);
    }

    /**
     * @param  Builder<Execution>  $query
     */
    public function retryMany(Builder $query): int
    {
        $executions = (clone $query)->whereIn('status', ExecutionStatus::retryable())
            ->with('libraryJob')
            ->get();

        foreach ($executions as $execution) {
            $this->requeue($execution);
        }

        return $executions->count();
    }

    /**
     * @param  Builder<Execution>  $query
     */
    public function startMany(Builder $query): int
    {
        return $this->resumeMany($query) + $this->retryMany($query);
    }

    /**
     * @param  Builder<Execution>  $query
     */
    public function deleteMany(Builder $query): int
    {
        // Running workers notice the missing row and terminate their process.
        return (clone $query)->delete();
    }

    private function requeue(Execution $execution): void
    {
        $fingerprint = $this->fingerprint($execution->file_path);

        $execution->update([
            'status' => ExecutionStatus::QUEUED,
            'progress' => null,
            'message' => null,
            'output' => null,
            'started_at' => null,
            'heartbeat_at' => null,
            'finished_at' => null,
            ...$fingerprint,
        ]);

        $execution->libraryJob?->job_id->dispatch($execution);
    }

    /**
     * @return array{file_size?: int, file_mtime?: int}
     */
    private function fingerprint(string $path): array
    {
        clearstatcache(true, $path);

        if (! is_file($path)) {
            return [];
        }

        return ['file_size' => (int) filesize($path), 'file_mtime' => (int) filemtime($path)];
    }
}
