<?php

namespace App\Observers;

use App\Jobs\OrchestrateWorkers;
use App\LibraryJobId;
use App\Models\Worker;

class WorkerObserver
{
    public function created(Worker $worker): void
    {
        $this->orchestrate($worker);
    }

    public function updated(Worker $worker): void
    {
        if ($worker->wasChanged(['concurrency', 'enabled', 'job_type'])) {
            $this->orchestrate($worker);
        }
    }

    public function deleted(Worker $worker): void
    {
        $this->orchestrate($worker);
    }

    private function orchestrate(Worker $worker): void
    {
        OrchestrateWorkers::dispatch($worker->job_type instanceof LibraryJobId ? $worker->job_type : null);
    }
}
