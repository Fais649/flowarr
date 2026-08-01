<?php

namespace App\Jobs;

use App\Models\Execution;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Symfony\Component\Process\Process;

abstract class ExecutionJob implements ShouldQueue
{
    use Queueable;

    protected ?Process $process;

    public function __construct(public Execution $execution) {}

    abstract public function handle(): void;
}
