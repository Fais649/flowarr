<?php

namespace App\Observers;

use App\Jobs\OrchestrateWorkers;
use App\Models\Worker;

class WorkerObserver
{
    public function updated(Worker $worker): void
    {
        if ($worker->wasChanged('concurrency', 'enabled')) {
            OrchestrateWorkers::dispatch($worker);
        }
    }

    public function deleted(Worker $worker): void
    {
        OrchestrateWorkers::dispatch($worker);
    }

    public function created(Worker $worker): void
    {
        OrchestrateWorkers::dispatch($worker);
    }
}
