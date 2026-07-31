<?php

namespace App\Jobs\Contracts;

use App\Services\SupervisorService;
use Illuminate\Log\Logger;

interface OrchestrationJob
{
    public function handle(SupervisorService $supervisor, Logger $logger): void;
}
