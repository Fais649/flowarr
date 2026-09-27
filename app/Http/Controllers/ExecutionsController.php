<?php

namespace App\Http\Controllers;

use App\ExecutionStatus;
use App\Http\Requests\ExecutionBatchRequest;
use App\Http\Requests\ExecutionFilterRequest;
use App\Models\Execution;
use App\Models\Library;
use App\Services\ExecutionControl;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ExecutionsController extends Controller
{
    public function __construct(private ExecutionControl $control) {}

    public function index(ExecutionFilterRequest $request): Response
    {
        $query = Execution::with('libraryJob.library');

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('library_id')) {
            $query->whereHas('libraryJob', fn ($q) => $q->where('library_id', $request->integer('library_id')));
        }

        if ($request->filled('search')) {
            $query->where('file_path', 'ilike', '%'.addcslashes((string) $request->input('search'), '%_\\').'%');
        }

        $sortField = $request->input('sort', 'created_at');
        $sortDir = $request->input('direction', 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortField, $sortDir)->orderBy('id', $sortDir);

        return Inertia::render('executions/index', [
            'executions' => $query->paginate($request->integer('per_page', 15))->withQueryString(),
            'filters' => $request->only(['status', 'library_id', 'search', 'sort', 'direction']),
            'statuses' => collect(ExecutionStatus::cases())->map(fn (ExecutionStatus $s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ]),
            'libraries' => Library::orderBy('base_path')->get(['id', 'base_path']),
        ]);
    }

    public function show(Execution $execution): Response
    {
        $execution->load(['libraryJob.library', 'worker']);

        return Inertia::render('executions/[id]/index', [
            'execution' => [
                ...$execution->toArray(),
                'output' => $execution->output,
                'duration_seconds' => $execution->durationSeconds(),
            ],
        ]);
    }

    public function retry(Execution $execution): RedirectResponse
    {
        return $this->respond($this->control->retry($execution), 'Execution queued again.');
    }

    public function cancel(Execution $execution): RedirectResponse
    {
        return $this->stop($execution);
    }

    public function start(Execution $execution): RedirectResponse
    {
        return $this->respond($this->control->start($execution), 'Execution started.');
    }

    public function pause(Execution $execution): RedirectResponse
    {
        return $this->respond($this->control->pause($execution), 'Execution paused.');
    }

    public function resume(Execution $execution): RedirectResponse
    {
        return $this->respond($this->control->resume($execution), 'Execution resumed.');
    }

    public function stop(Execution $execution): RedirectResponse
    {
        return $this->respond($this->control->stop($execution), 'Execution stopped.');
    }

    public function destroy(Execution $execution): RedirectResponse
    {
        $execution->delete();

        if (url()->previous() === route('executions.show', $execution)) {
            return to_route('executions.index');
        }

        return redirect()->back();
    }

    public function batchStart(ExecutionBatchRequest $request): RedirectResponse
    {
        return $this->respondBatch($this->control->startMany($request->executions()), 'started');
    }

    public function batchRetry(ExecutionBatchRequest $request): RedirectResponse
    {
        return $this->respondBatch($this->control->retryMany($request->executions()), 'queued again');
    }

    public function batchPause(ExecutionBatchRequest $request): RedirectResponse
    {
        return $this->respondBatch($this->control->pauseMany($request->executions()), 'paused');
    }

    public function batchResume(ExecutionBatchRequest $request): RedirectResponse
    {
        return $this->respondBatch($this->control->resumeMany($request->executions()), 'resumed');
    }

    public function batchStop(ExecutionBatchRequest $request): RedirectResponse
    {
        return $this->respondBatch($this->control->stopMany($request->executions()), 'stopped');
    }

    public function batchDelete(ExecutionBatchRequest $request): RedirectResponse
    {
        return $this->respondBatch($this->control->deleteMany($request->executions()), 'deleted');
    }

    private function respond(bool $changed, string $message): RedirectResponse
    {
        if ($changed) {
            Inertia::flash('toast', ['type' => 'success', 'message' => $message]);
        }

        return redirect()->back();
    }

    private function respondBatch(int $count, string $verb): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => "{$count} execution(s) {$verb}."]);

        return redirect()->back();
    }
}
