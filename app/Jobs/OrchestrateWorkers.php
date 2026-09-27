<?php

namespace App\Jobs;

use App\Jobs\Contracts\OrchestrateJob;
use App\LibraryJobId;
use App\Models\Worker;
use App\OrchestrateJobQueue;
use App\Services\SupervisorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Log\Logger;
use Illuminate\Queue\Attributes\Queue;

/**
 * Scales each job type's supervisord process pool to the concurrency of its
 * enabled worker configuration (0 processes when disabled or deleted).
 */
#[Queue(queue: OrchestrateJobQueue::ORCHESTRATE_WORKERS)]
class OrchestrateWorkers implements OrchestrateJob, ShouldQueue
{
    use Queueable;

    private const MAX_PROCS = Worker::MAX_CONCURRENCY;

    public function __construct(private ?LibraryJobId $jobType = null) {}

    public function handle(SupervisorService $supervisor, Logger $logger): void
    {
        $jobTypes = $this->jobType !== null ? [$this->jobType] : LibraryJobId::cases();

        foreach ($jobTypes as $jobType) {
            $target = $this->targetProcesses($jobType);
            $logger->info("{$jobType->supervisorProgram()} (target: {$target})");
            $this->syncProgram($supervisor, $jobType->supervisorProgram(), $target);
        }

        $logger->info('Queue worker pools orchestrated successfully.');
    }

    public function targetProcesses(LibraryJobId $jobType): int
    {
        $concurrency = (int) Worker::where('job_type', $jobType)
            ->where('enabled', true)
            ->max('concurrency');

        return max(0, min($concurrency, self::MAX_PROCS));
    }

    private function syncProgram(SupervisorService $supervisor, string $program, int $target): void
    {
        for ($i = 0; $i < $target; $i++) {
            $supervisor->startWorker($program, $i);
        }

        for ($i = $target; $i < self::MAX_PROCS; $i++) {
            $supervisor->stopWorker($program, $i);
        }
    }
}
