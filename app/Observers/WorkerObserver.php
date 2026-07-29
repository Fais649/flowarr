<?php

namespace App\Observers;

use App\Jobs\OrchestrateWorkersJob;
use App\Models\Worker;

class WorkerObserver
{
    public function updated(Worker $worker): void
    {
        if ($worker->wasChanged('concurrency', 'enabled')) {
            OrchestrateWorkersJob::dispatch($worker);
        }
    }

    public function deleted(Worker $worker): void
    {
        OrchestrateWorkersJob::dispatch($worker);
    }

    public function created(Worker $worker): void
    {
        OrchestrateWorkersJob::dispatch($worker);
    }
}
