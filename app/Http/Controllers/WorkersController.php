<?php

namespace App\Http\Controllers;

use App\ExecutionStatus;
use App\Http\Requests\UpdateWorkerRequest;
use App\LibraryJobId;
use App\Models\Execution;
use App\Models\Worker;
use App\Services\ExecutionControl;
use App\Services\HardwareCapabilities;
use App\Services\ProcessingGate;
use App\Services\StreamTracker;
use App\Services\SupervisorService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class WorkersController extends Controller
{
    public function __construct(
        private ExecutionControl $control,
        private ProcessingGate $gate,
    ) {}

    public function index(SupervisorService $supervisor, HardwareCapabilities $capabilities, StreamTracker $streams): Response
    {
        $processCounts = $supervisor->runningProcessCounts();

        $workers = Worker::orderBy('created_at')->orderBy('id')->get()->map(fn (Worker $worker) => [
            ...$worker->toArray(),
            ...$this->workerStats($worker, $processCounts),
        ]);

        return Inertia::render('workers/index', [
            'workers' => $workers,
            'maxConcurrency' => Worker::MAX_CONCURRENCY,
            'processing' => $this->gate->summary(),
            'streams' => array_values($streams->sessions()),
            'capabilities' => Inertia::defer(fn () => $capabilities->detect()),
        ]);
    }

    public function show(Worker $worker, SupervisorService $supervisor): Response
    {
        $recentExecutions = Execution::with('libraryJob.library')
            ->whereIn('library_job_id', fn ($q) => $q->select('id')->from('library_jobs')->where('job_id', $worker->job_type))
            ->latest('id')
            ->limit(10)
            ->get();

        return Inertia::render('workers/[id]/index', [
            'worker' => [
                ...$worker->toArray(),
                ...$this->workerStats($worker, $supervisor->runningProcessCounts()),
            ],
            'maxConcurrency' => Worker::MAX_CONCURRENCY,
            'recentExecutions' => $recentExecutions,
        ]);
    }

    public function update(UpdateWorkerRequest $request, Worker $worker): RedirectResponse
    {
        $worker->update($request->validated());

        return redirect()->back();
    }

    public function start(Worker $worker): RedirectResponse
    {
        return $this->respond($this->control->startMany($this->executionsFor($worker)), 'started');
    }

    public function pause(Worker $worker): RedirectResponse
    {
        return $this->respond($this->control->pauseMany($this->executionsFor($worker)), 'paused');
    }

    public function resume(Worker $worker): RedirectResponse
    {
        return $this->respond($this->control->resumeMany($this->executionsFor($worker)), 'resumed');
    }

    public function stop(Worker $worker): RedirectResponse
    {
        return $this->respond($this->control->stopMany($this->executionsFor($worker)), 'stopped');
    }

    /**
     * Resume processing: lift a manual pause and continue individually paused executions.
     */
    public function startAll(): RedirectResponse
    {
        $this->gate->resume();
        $this->control->resumeMany(Execution::query());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Processing resumed.']);

        return redirect()->back();
    }

    /**
     * Hold all processing: running jobs are suspended and queued jobs wait.
     */
    public function pauseAll(): RedirectResponse
    {
        $this->gate->pause();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Processing paused.']);

        return redirect()->back();
    }

    public function resumeAll(): RedirectResponse
    {
        return $this->startAll();
    }

    public function stopAll(): RedirectResponse
    {
        return $this->respond($this->control->stopMany(Execution::query()), 'stopped');
    }

    public function refreshCapabilities(HardwareCapabilities $capabilities): RedirectResponse
    {
        $capabilities->detect(fresh: true);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Hardware capabilities re-detected.']);

        return redirect()->back();
    }

    public function clearStreams(StreamTracker $streams): RedirectResponse
    {
        $streams->clear();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Active streams cleared.']);

        return redirect()->back();
    }

    /**
     * @return Builder<Execution>
     */
    private function executionsFor(Worker $worker): Builder
    {
        return Execution::whereHas('libraryJob', fn ($q) => $q->where('job_id', $worker->job_type));
    }

    /**
     * @param  array<string, int>|null  $processCounts
     * @return array{running_processes: int|null, processing_count: int, queued_count: int}
     */
    private function workerStats(Worker $worker, ?array $processCounts): array
    {
        $counts = $this->executionsFor($worker)
            ->whereIn('status', [ExecutionStatus::PROCESSING, ExecutionStatus::PAUSED, ExecutionStatus::QUEUED])
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $program = $worker->job_type instanceof LibraryJobId ? $worker->job_type->supervisorProgram() : null;

        return [
            'running_processes' => $processCounts !== null && $program !== null ? ($processCounts[$program] ?? 0) : null,
            'processing_count' => (int) ($counts[ExecutionStatus::PROCESSING->value] ?? 0) + (int) ($counts[ExecutionStatus::PAUSED->value] ?? 0),
            'queued_count' => (int) ($counts[ExecutionStatus::QUEUED->value] ?? 0),
        ];
    }

    private function respond(int $count, string $verb): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => "{$count} execution(s) {$verb}."]);

        return redirect()->back();
    }
}
