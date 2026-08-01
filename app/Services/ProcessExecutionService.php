<?php

namespace App\Services;

use App\ExecutionStatus;
use App\Models\Execution;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

abstract class ProcessExecutionService
{
    protected Execution $execution;

    protected ?Process $process;

    public function process(Execution $execution): ProcessExecutionResult
    {
        $this->execution = $execution;

        $this->updateExecution(['status' => ExecutionStatus::PROCESSING, 'started_at' => now()]);

        if (! file_exists($this->execution->file_path)) {
            Log::info(implode(', ', $this->execution->toArray()));

            return $this->endProcess(
                ['status' => ExecutionStatus::FAILED, 'finished_at' => now()],
                ProcessExecutionResultStatus::FAILED,
                sprintf('Failed to process execution. Falafel not found: %s', $this->execution->file_path)
            );
        }

        $this->process = $this->buildProcess();
        if (! $this->process) {
            return $this->endProcess(
                ['status' => ExecutionStatus::FAILED, 'finished_at' => now()],
                ProcessExecutionResultStatus::FAILED,
                sprintf('Failed to build process for %s', $this->execution->file_path)
            );
        }

        $this->startProcess();
        $this->awaitProcessCompleted();

        if ($this->isProcessSuccessful()) {
            return $this->endProcess(
                ['status' => ExecutionStatus::COMPLETED, 'finished_at' => now()],
                ProcessExecutionResultStatus::SUCCESS,
                sprintf('Finished processing transcode for %s', $this->execution->file_path)
            );
        }

        return $this->endProcess(
            ['status' => ExecutionStatus::FAILED, 'finished_at' => now()],
            ProcessExecutionResultStatus::FAILED,
            sprintf('Process failed for %s: %s', $this->execution->file_path, $this->process->getErrorOutput())
        );
    }

    protected function endProcess(array $attributes, ProcessExecutionResultStatus $status, string $message): ProcessExecutionResult
    {
        $this->updateExecution($attributes);
        Log::info($message);

        return new ProcessExecutionResult($status, $message);
    }

    protected function awaitProcessCompleted(): void
    {
        while ($this->process->isRunning()) {
            if ($this->shouldPause()) {
                posix_kill($this->process->getPid(), SIGSTOP);

                while ($this->shouldPause()) {
                    sleep(2);
                }

                posix_kill($this->process->getPid(), SIGCONT);
            }
            $this->process->checkTimeout();
            usleep(200000);
        }
    }

    public function updateExecution(array $attributes): void
    {
        $this->execution->update($attributes);
    }

    protected function shouldPause(): bool
    {
        return Cache::get('media_processing_paused') || Cache::get('active_streams', 0) > 0;
    }

    protected function startProcess(): void
    {
        $this->process->start();
    }

    protected function isProcessSuccessful(): bool
    {
        return $this->process->isSuccessful();
    }

    abstract protected function buildProcess(): Process;
}
