<?php

namespace App\Jobs\Concerns;

use App\ExecutionStatus;
use App\LibraryJobId;
use App\Models\Execution;
use App\Settings;
use Illuminate\Queue\InteractsWithQueue;

trait HasPauseAndConcurrency
{
    use InteractsWithQueue;

    protected function shouldPause(): bool
    {
        return Settings::isPaused();
    }

    protected function concurrencyLimit(LibraryJobId $jobType): int
    {
        return Settings::concurrency($jobType->value);
    }

    protected function activeCount(LibraryJobId $jobType): int
    {
        return Execution::where('status', ExecutionStatus::PROCESSING)
            ->whereHas('libraryJob', fn ($q) => $q->where('job_id', $jobType->value))
            ->count();
    }

    protected function isAtCapacity(LibraryJobId $jobType): bool
    {
        return $this->activeCount($jobType) >= $this->concurrencyLimit($jobType);
    }

    protected function releaseIfAtCapacity(LibraryJobId $jobType): void
    {
        if ($this->isAtCapacity($jobType) && isset($this->job)) {
            $this->release(30);
        }
    }
}
