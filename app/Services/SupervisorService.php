<?php

namespace App\Services;

use Illuminate\Log\Logger;
use LogicException;
use Symfony\Component\Process\Process;

class SupervisorService
{
    private const SOCKET = 'unix:///var/run/supervisor.sock';

    public function __construct(
        protected Logger $logger,
    ) {}

    public function startWorker(string $program, int $index): bool
    {
        return $this->runCommand(['supervisorctl', 'start', $this->processName($program, $index)]);
    }

    public function stopWorker(string $program, int $index): bool
    {
        return $this->runCommand(['supervisorctl', 'stop', $this->processName($program, $index)]);
    }

    public function getStatus(): ?string
    {
        $process = new Process(['supervisorctl', '-s', self::SOCKET, 'status']);

        try {
            $process->run();
        } catch (\Throwable) {
            return null;
        }

        // supervisorctl exits non-zero when any process is stopped, so rely on output.
        $output = $process->getOutput();

        return $output === '' ? null : $output;
    }

    /**
     * Number of RUNNING processes per supervisord program, or null when
     * supervisord is not reachable (e.g. local development).
     *
     * @return array<string, int>|null
     */
    public function runningProcessCounts(): ?array
    {
        $status = $this->getStatus();

        if ($status === null) {
            return null;
        }

        return self::parseRunningCounts($status);
    }

    /**
     * @return array<string, int>
     */
    public static function parseRunningCounts(string $status): array
    {
        $counts = [];

        foreach (explode("\n", $status) as $line) {
            if (! preg_match('/^([\w-]+)(?::\S+)?\s+(\w+)/', trim($line), $matches)) {
                continue;
            }

            $counts[$matches[1]] ??= 0;

            if ($matches[2] === 'RUNNING') {
                $counts[$matches[1]]++;
            }
        }

        return $counts;
    }

    /**
     * @param  array<string>  $command
     *
     * @throws LogicException
     */
    public function runCommand(array $command): bool
    {
        array_splice($command, 1, 0, ['-s', self::SOCKET]);

        $process = new Process($command);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->logger->warning(sprintf('supervisorctl failed: %s %s', implode(' ', $command), trim($process->getErrorOutput().$process->getOutput())));
        }

        return $process->isSuccessful();
    }

    private function processName(string $program, int $index): string
    {
        return sprintf('%s:%s_%02d', $program, $program, $index);
    }
}
