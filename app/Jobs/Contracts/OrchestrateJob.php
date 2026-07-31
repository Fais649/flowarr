<?php

namespace App\Jobs\Contracts;

use App\Services\SupervisorService;
use Illuminate\Log\Logger;

interface OrchestrateJob
{
    public function handle(SupervisorService $supervisor, Logger $logger): void;
}
