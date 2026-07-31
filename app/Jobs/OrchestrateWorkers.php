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

#[Queue(queue: OrchestrateJobQueue::ORCHESTRATE_WORKERS)]
class OrchestrateWorkers implements OrchestrateJob, ShouldQueue
{
    use Queueable;

    private const MAX_PROCS = 10;

    public function __construct(private ?Worker $worker = null) {}

    public function handle(SupervisorService $supervisor, Logger $logger): void
    {
        if ($this->worker) {
            $this->processWorker($supervisor, $logger, $this->worker);

            return;
        }

        $workers = Worker::all();

        if ($workers->isEmpty()) {
            $logger->warning('No worker configurations found in the database.');

            return;
        }

        foreach ($workers as $worker) {
            $this->processWorker($supervisor, $logger, $worker);
        }

        $logger->info('Queue worker pools orchestrated successfully.');
    }

    private function processWorker(SupervisorService $supervisor, Logger $logger, Worker $worker): void
    {
        $programName = match ($worker->job_type) {
            LibraryJobId::TRANSCODE_MEDIA => 'Transcoder',
            LibraryJobId::EXTRACT_SUBTITLES => 'ExtractSubs',
            LibraryJobId::CONVERT_SUBTITLE => 'ConvertSubs',
            default => null,
        };

        if ($programName) {
            $target = $worker->enabled ? $worker->concurrency : 0;
            $logger->info("{$programName} (target: {$target})");
            $this->syncProgram($supervisor, $programName, $target, $logger);
        }
    }

    private function syncProgram(SupervisorService $supervisor, string $program, int $target, Logger $logger): void
    {
        for ($i = 0; $i < $target; $i++) {
            $logger->info($supervisor->startWorker($program, $i) ? 'Started' : 'Failed');
        }

        for ($i = $target; $i < self::MAX_PROCS; $i++) {
            $logger->info($supervisor->stopWorker($program, $i) ? 'Stopped' : 'Failed');
        }
    }
}
