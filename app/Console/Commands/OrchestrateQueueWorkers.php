<?php

namespace App\Console\Commands;

use App\Jobs\OrchestrateWorkersJob;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('queue:orchestrate')]
#[Description('Orchestrate Supervisor queue worker process pool scaling from database configuration')]
class OrchestrateQueueWorkers extends Command
{
    public function handle(): void
    {
        OrchestrateWorkersJob::dispatch()->delay(now()->addSeconds(10));
        $this->info('OrchestrateWorkersJob dispatched');
    }
}
