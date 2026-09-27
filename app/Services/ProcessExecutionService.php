<?php

namespace App\Services;

use App\ExecutionStatus;
use App\Models\Execution;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Runs the external process behind an execution and keeps the execution
 * record in sync with it.
 *
 * While the process runs, the execution row is polled so that pause, resume
 * and stop requests from the UI/API take effect on the real process tree,
 * and the processing gate (manual pause, active streams, schedule window)
 * suspends the process with SIGSTOP until processing is allowed again.
 */
abstract class ProcessExecutionService
{
    /** Seconds between checks of the execution status and processing gate. */
    protected const CONTROL_INTERVAL = 1.0;

    /** Seconds between heartbeat/progress writes to the database. */
    protected const HEARTBEAT_INTERVAL = 5.0;

    /** Seconds between gate checks while a queued execution waits to start. */
    protected const GATE_POLL_SECONDS = 5;

    /** Characters of process output kept on the execution record. */
    protected const OUTPUT_LIMIT = 20000;

    protected Execution $execution;

    protected ?Process $process = null;

    /** Media duration in seconds, used to derive progress from ffmpeg output. */
    protected ?float $durationSeconds = null;

    private string $output = '';

    private ?string $lastProgressLine = null;

    private ?float $progress = null;

    private bool $suspended = false;

    public function __construct(
        protected ProcessingGate $gate,
        protected ProcessSignaller $signaller,
    ) {}

    abstract protected function buildProcess(): Process;

    public function process(Execution $execution): ProcessExecutionResult
    {
        $this->execution = $execution;

        if (! file_exists($execution->file_path)) {
            return $this->fail(sprintf('File not found: %s', $execution->file_path));
        }

        if (! $this->waitForGate()) {
            return $this->finishStopped();
        }

        $this->execution->update([
            'status' => ExecutionStatus::PROCESSING,
            'started_at' => now(),
            'heartbeat_at' => now(),
            'finished_at' => null,
            'progress' => null,
            'message' => null,
            'output' => null,
        ]);

        try {
            $this->process = $this->buildProcess();
            $this->process->setTimeout(null);
            $this->process->start();
        } catch (Throwable $e) {
            return $this->fail(sprintf('Failed to start process: %s', $e->getMessage()));
        }

        if (! $this->monitor()) {
            return $this->finishStopped();
        }

        if ($this->process->isSuccessful()) {
            return $this->finish(
                ExecutionStatus::COMPLETED,
                ProcessExecutionResultStatus::SUCCESS,
                sprintf('Finished processing %s', $execution->file_path),
                ['progress' => 100],
            );
        }

        return $this->fail(sprintf(
            'Process exited with code %s: %s',
            $this->process->getExitCode() ?? 'unknown',
            $this->errorSummary(),
        ));
    }

    /**
     * Hold a queued execution until the processing gate opens.
     *
     * @return bool false when the execution was stopped or deleted while waiting
     */
    protected function waitForGate(): bool
    {
        $announced = false;

        while (! $this->gate->isOpen()) {
            if (! $announced) {
                $this->execution->update(['message' => $this->holdMessage()]);
                $announced = true;
            }

            Sleep::for(self::GATE_POLL_SECONDS)->seconds();

            if (! $this->isStillWanted()) {
                return false;
            }
        }

        return $this->isStillWanted();
    }

    /**
     * Supervise the running process until it exits or is stopped.
     *
     * @return bool false when the process was terminated because of a stop request
     */
    protected function monitor(): bool
    {
        $lastControl = 0.0;
        $lastHeartbeat = microtime(true);

        while ($this->process->isRunning()) {
            $this->collectOutput();
            $now = microtime(true);

            if ($now - $lastControl >= static::CONTROL_INTERVAL) {
                $lastControl = $now;

                if (! $this->applyControlState()) {
                    $this->terminate();

                    return false;
                }
            }

            if ($now - $lastHeartbeat >= static::HEARTBEAT_INTERVAL) {
                $lastHeartbeat = $now;
                $this->heartbeat();
            }

            Sleep::usleep(200000);
        }

        $this->collectOutput();

        return true;
    }

    /**
     * Suspend or resume the process according to the execution status and the
     * processing gate.
     *
     * @return bool false when the execution should be stopped
     */
    protected function applyControlState(): bool
    {
        $status = $this->currentStatus();

        if ($status === null || $status === ExecutionStatus::STOPPED) {
            return false;
        }

        $pausedByUser = $status === ExecutionStatus::PAUSED;
        $hold = $pausedByUser || ! $this->gate->isOpen();

        if ($hold && ! $this->suspended) {
            $this->signaller->signalTree((int) $this->process->getPid(), SIGSTOP);
            $this->suspended = true;
            $this->execution->update(['message' => $pausedByUser ? 'Paused by user' : $this->holdMessage('On hold')]);
        } elseif (! $hold && $this->suspended) {
            $this->signaller->signalTree((int) $this->process->getPid(), SIGCONT);
            $this->suspended = false;
            $this->execution->update(['message' => null]);
        }

        return true;
    }

    protected function terminate(): void
    {
        $pid = $this->process->getPid();

        if ($pid === null) {
            return;
        }

        $children = $this->signaller->descendants($pid);

        if ($this->suspended) {
            $this->signaller->signalAll([$pid, ...$children], SIGCONT);
        }

        // The output is discarded, so don't let ffmpeg spend seconds flushing its
        // encoder: kill the children outright, then let the script's TERM trap
        // remove its temp files.
        $this->signaller->signalAll($children, SIGKILL);
        $this->signaller->signalAll([$pid], SIGTERM);
        $this->process->stop(10);
    }

    protected function heartbeat(): void
    {
        $this->execution->update([
            'heartbeat_at' => now(),
            'progress' => $this->progress,
            'output' => $this->outputForStorage(),
        ]);
    }

    /**
     * Whether the execution still exists and has not been stopped.
     */
    protected function isStillWanted(): bool
    {
        $status = $this->currentStatus();

        return $status !== null && $status !== ExecutionStatus::STOPPED;
    }

    /**
     * The status as currently stored, which the UI/API may have changed
     * since this worker loaded the execution. Null when it was deleted.
     */
    protected function currentStatus(): ?ExecutionStatus
    {
        $status = Execution::whereKey($this->execution->id)->value('status');

        return $status instanceof ExecutionStatus ? $status : ExecutionStatus::tryFrom((string) $status);
    }

    protected function holdMessage(string $prefix = 'Waiting'): string
    {
        $labels = [
            ProcessingGate::REASON_MANUAL => 'processing is paused',
            ProcessingGate::REASON_STREAMS => 'media is being streamed',
            ProcessingGate::REASON_SCHEDULE => 'outside the processing window',
        ];

        $reasons = array_map(fn (string $reason) => $labels[$reason] ?? $reason, $this->gate->pauseReasons());

        return $prefix.': '.($reasons === [] ? 'processing is held' : implode(', ', $reasons));
    }

    protected function collectOutput(): void
    {
        if ($this->process === null) {
            return;
        }

        $chunk = $this->process->getIncrementalOutput().$this->process->getIncrementalErrorOutput();

        if ($chunk === '') {
            return;
        }

        foreach (preg_split('/\r\n|\r|\n/', $chunk) ?: [] as $line) {
            $this->recordOutputLine($line);
        }

        if (strlen($this->output) > static::OUTPUT_LIMIT * 2) {
            $this->output = substr($this->output, -static::OUTPUT_LIMIT);
        }
    }

    private function recordOutputLine(string $line): void
    {
        if (trim($line) === '') {
            return;
        }

        // ffmpeg progress lines, e.g. "frame=  240 fps= 48 ... time=00:00:10.01 ..."
        if (preg_match('/time=(\d+):(\d{2}):(\d{2}(?:\.\d+)?)/', $line, $matches)) {
            $this->lastProgressLine = trim($line);

            if ($this->durationSeconds !== null && $this->durationSeconds > 0) {
                $elapsed = (int) $matches[1] * 3600 + (int) $matches[2] * 60 + (float) $matches[3];
                $this->progress = round(min(99.9, max(0, $elapsed / $this->durationSeconds * 100)), 2);
            }

            return;
        }

        $this->output .= $line."\n";
    }

    protected function outputForStorage(): ?string
    {
        $output = $this->output.($this->lastProgressLine !== null ? $this->lastProgressLine."\n" : '');

        if ($output === '') {
            return null;
        }

        return strlen($output) > static::OUTPUT_LIMIT
            ? '…'.substr($output, -static::OUTPUT_LIMIT)
            : $output;
    }

    protected function errorSummary(): string
    {
        $lines = array_values(array_filter(explode("\n", $this->output), fn (string $l) => trim($l) !== ''));

        if ($lines === []) {
            return 'no output';
        }

        return Str::limit(implode("\n", array_slice($lines, -8)), 2000);
    }

    protected function fail(string $message): ProcessExecutionResult
    {
        return $this->finish(ExecutionStatus::FAILED, ProcessExecutionResultStatus::FAILED, $message);
    }

    protected function finishStopped(): ProcessExecutionResult
    {
        if (! Execution::whereKey($this->execution->id)->exists()) {
            return new ProcessExecutionResult(ProcessExecutionResultStatus::STOPPED, 'Execution was deleted');
        }

        return $this->finish(ExecutionStatus::STOPPED, ProcessExecutionResultStatus::STOPPED, 'Stopped by user');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function finish(ExecutionStatus $status, ProcessExecutionResultStatus $result, string $message, array $attributes = []): ProcessExecutionResult
    {
        $this->execution->update([
            'status' => $status,
            'finished_at' => now(),
            'heartbeat_at' => now(),
            'message' => $status === ExecutionStatus::COMPLETED ? null : $message,
            'output' => $this->outputForStorage(),
            ...$attributes,
        ]);

        Log::info(sprintf('Execution %d %s: %s', $this->execution->id, $status->value, $message));

        app(ExecutionNotifier::class)->executionFinished($this->execution);

        return new ProcessExecutionResult($result, $message);
    }

    protected function script(string $name): string
    {
        return base_path("scripts/{$name}");
    }
}
