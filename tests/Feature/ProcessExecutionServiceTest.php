<?php

use App\ExecutionStatus;
use App\Models\Execution;
use App\Services\ProcessExecutionResultStatus;
use App\Services\ProcessExecutionService;
use App\Services\ProcessingGate;
use App\Services\ProcessSignaller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Sleep;
use Symfony\Component\Process\Process;

/**
 * Exercises the supervision loop with real child processes. Sleep is faked
 * so the loop spins without delay; the fake-sleep callback plays the role of
 * the UI changing the execution while the worker is running it.
 */
beforeEach(function () {
    $this->workDir = storage_path('app/testing_process_'.uniqid());
    File::makeDirectory($this->workDir, 0777, true, true);
    $this->pidFile = $this->workDir.'/child.pid';
    $this->input = $this->workDir.'/input.mkv';
    File::put($this->input, 'not really a video');

    $this->execution = Execution::factory()->create([
        'file_path' => $this->input,
        'status' => ExecutionStatus::QUEUED,
        'started_at' => null,
        'finished_at' => null,
    ]);

    Sleep::fake();
});

afterEach(function () {
    File::deleteDirectory($this->workDir);
});

/**
 * @param  list<string>  $command
 */
function fakeService(array $command, ?float $duration = null): ProcessExecutionService
{
    return new class(app(ProcessingGate::class), app(ProcessSignaller::class), $command, $duration) extends ProcessExecutionService
    {
        protected const CONTROL_INTERVAL = 0.0;

        protected const HEARTBEAT_INTERVAL = 0.0;

        /**
         * @param  list<string>  $command
         */
        public function __construct(ProcessingGate $gate, ProcessSignaller $signaller, private array $command, ?float $duration)
        {
            parent::__construct($gate, $signaller);
            $this->durationSeconds = $duration;
        }

        protected function buildProcess(): Process
        {
            return new Process($this->command);
        }
    };
}

function processState(int $pid): ?string
{
    $stat = @file_get_contents("/proc/{$pid}/stat");

    return $stat === false ? null : substr($stat, strrpos($stat, ')') + 2, 1);
}

function isAlive(int $pid): bool
{
    $state = processState($pid);

    return $state !== null && $state !== 'Z';
}

it('completes a successful process and stores its output', function () {
    $result = fakeService(['bash', '-c', 'echo "hello from the script"'])->process($this->execution);

    $this->execution->refresh();
    expect($result->status)->toBe(ProcessExecutionResultStatus::SUCCESS)
        ->and($this->execution->status)->toBe(ExecutionStatus::COMPLETED)
        ->and($this->execution->progress)->toBe(100.0)
        ->and($this->execution->output)->toContain('hello from the script')
        ->and($this->execution->message)->toBeNull();
});

it('fails with the tail of the process output as message', function () {
    fakeService(['bash', '-c', 'echo "Unknown encoder" >&2; exit 3'])->process($this->execution);

    $this->execution->refresh();
    expect($this->execution->status)->toBe(ExecutionStatus::FAILED)
        ->and($this->execution->message)->toContain('exited with code 3')
        ->and($this->execution->message)->toContain('Unknown encoder');
});

it('derives progress from ffmpeg time output', function () {
    $progress = [];
    Sleep::whenFakingSleep(function () use (&$progress) {
        $progress[] = Execution::find($this->execution->id)->progress;
    });

    fakeService(['bash', '-c', 'echo "frame=10 fps=5 time=00:00:05.00 bitrate=1k" >&2; sleep 0.5'], duration: 10)
        ->process($this->execution);

    expect($progress)->toContain(50.0)
        ->and($this->execution->refresh()->progress)->toBe(100.0);
});

it('suspends and resumes the whole process tree when the execution is paused', function () {
    $states = [];
    $tick = 0;

    Sleep::whenFakingSleep(function () use (&$tick, &$states) {
        if (! File::exists($this->pidFile)) {
            return;
        }

        $tick++;

        $child = (int) File::get($this->pidFile);

        match (true) {
            $tick === 20 => Execution::whereKey($this->execution->id)->update(['status' => ExecutionStatus::PAUSED]),
            $tick === 40 => [$states['paused'] = processState($child), Execution::whereKey($this->execution->id)->update(['status' => ExecutionStatus::PROCESSING])],
            $tick === 60 => [$states['resumed'] = processState($child), Execution::whereKey($this->execution->id)->update(['status' => ExecutionStatus::STOPPED])],
            default => null,
        };
    });

    $script = sprintf('sleep 30 & echo $! > %s; wait', escapeshellarg($this->pidFile));
    $result = fakeService(['bash', '-c', $script])->process($this->execution);

    $child = (int) File::get($this->pidFile);

    expect($states['paused'])->toBe('T')
        ->and($states['resumed'])->toBeIn(['S', 'R'])
        ->and($result->status)->toBe(ProcessExecutionResultStatus::STOPPED)
        ->and($this->execution->refresh()->status)->toBe(ExecutionStatus::STOPPED)
        ->and(isAlive($child))->toBeFalse();
})->skip(PHP_OS_FAMILY !== 'Linux', 'Process tree inspection needs /proc');

it('terminates the process when the execution is deleted', function () {
    Sleep::whenFakingSleep(function () {
        if (File::exists($this->pidFile)) {
            Execution::whereKey($this->execution->id)->delete();
        }
    });

    $script = sprintf('sleep 30 & echo $! > %s; wait', escapeshellarg($this->pidFile));
    $result = fakeService(['bash', '-c', $script])->process($this->execution);

    expect($result->status)->toBe(ProcessExecutionResultStatus::STOPPED)
        ->and(isAlive((int) File::get($this->pidFile)))->toBeFalse()
        ->and(Execution::count())->toBe(0);
})->skip(PHP_OS_FAMILY !== 'Linux', 'Process tree inspection needs /proc');

it('waits for the processing gate before starting', function () {
    $gate = app(ProcessingGate::class);
    $gate->pause();
    $messages = [];

    Sleep::whenFakingSleep(function () use ($gate, &$messages) {
        $messages[] = Execution::find($this->execution->id)->message;
        $gate->resume();
    });

    fakeService(['true'])->process($this->execution);

    expect($messages[0])->toBe('Waiting: processing is paused')
        ->and($this->execution->refresh()->status)->toBe(ExecutionStatus::COMPLETED);
});

it('suspends a running process while the gate is closed', function () {
    $gate = app(ProcessingGate::class);
    $tick = 0;
    $state = null;

    Sleep::whenFakingSleep(function () use ($gate, &$tick, &$state) {
        if (! File::exists($this->pidFile)) {
            return;
        }

        $tick++;

        $child = (int) File::get($this->pidFile);

        match (true) {
            $tick === 20 => $gate->pause(),
            $tick === 40 => [$state = processState($child), $gate->resume(), posix_kill($child, SIGTERM)],
            default => null,
        };
    });

    $script = sprintf('sleep 30 & echo $! > %s; wait', escapeshellarg($this->pidFile));
    fakeService(['bash', '-c', $script])->process($this->execution);

    expect($state)->toBe('T');
})->skip(PHP_OS_FAMILY !== 'Linux', 'Process tree inspection needs /proc');

it('stops a queued execution that is cancelled while waiting for the gate', function () {
    app(ProcessingGate::class)->pause();

    Sleep::whenFakingSleep(function () {
        Execution::whereKey($this->execution->id)->update(['status' => ExecutionStatus::STOPPED]);
    });

    $result = fakeService(['true'])->process($this->execution);

    expect($result->status)->toBe(ProcessExecutionResultStatus::STOPPED)
        ->and($this->execution->refresh()->status)->toBe(ExecutionStatus::STOPPED)
        ->and($this->execution->started_at)->toBeNull();
});
