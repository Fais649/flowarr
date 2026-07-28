<?php

namespace App\Services;

use Illuminate\Log\Logger;
use Symfony\Component\Process\Process;

class SupervisorService
{
    public function __construct(
        protected Logger $logger,
    ) {}

    public function startWorker(string $program, int $index): bool
    {
        $processName = sprintf('%s:%s_%02d', $program, $program, $index);

        $this->logger->info($processName);

        return $this->runCommand(['supervisorctl', 'start', $processName]);
    }

    public function stopWorker(string $program, int $index): bool
    {
        $processName = sprintf('%s:%s_%02d', $program, $program, $index);

        return $this->runCommand(['supervisorctl', 'stop', $processName]);
    }

    public function getStatus(): string
    {

        $process = new Process(['supervisorctl', 'status']);
        $process->run();

        return $process->getOutput();
    }

    public function runCommand(array $command): bool
    {
        array_splice($command, 1, 0, ['-s', 'unix:///var/run/supervisor.sock']);

        $process = new Process($command);
        $process->run();

        return $process->isSuccessful();
    }

    public function getRunningWorkers(): array {}
}
