<?php

namespace App\Http\Controllers;

use App\ExecutionStatus;
use App\Models\Execution;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExecutionsController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Execution::with('libraryJob.library');

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('library_id')) {
            $query->whereHas('libraryJob', fn ($q) => $q->where('library_id', $request->library_id));
        }

        $sortField = $request->get('sort', 'created_at');
        $sortDir = $request->get('direction', 'desc');
        $allowedSorts = ['created_at', 'status', 'file_path'];

        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        return Inertia::render('executions/index', [
            'executions' => $query->paginate($request->get('per_page', 15))->withQueryString(),
            'filters' => $request->only(['status', 'library_id', 'sort', 'direction']),
            'statuses' => collect(ExecutionStatus::cases())->map(fn (ExecutionStatus $s) => [
                'value' => $s->value,
                'label' => ucwords(str_replace('_', ' ', $s->value)),
            ]),
        ]);
    }

    public function show(Execution $execution): Response
    {
        $execution->load('libraryJob.library');

        return Inertia::render('executions/[id]/index', [
            'execution' => $execution,
        ]);
    }

    public function retry(Execution $execution): RedirectResponse
    {
        if ($execution->status !== ExecutionStatus::FAILED) {
            return redirect()->back();
        }

        Execution::create([
            'library_job_id' => $execution->library_job_id,
            'file_path' => $execution->file_path,
            'status' => ExecutionStatus::QUEUED,
        ]);

        return redirect()->back();
    }

    public function cancel(Execution $execution): RedirectResponse
    {
        if (! in_array($execution->status, [ExecutionStatus::QUEUED, ExecutionStatus::PROCESSING])) {
            return redirect()->back();
        }

        $execution->update(['status' => ExecutionStatus::STOPPED]);

        return redirect()->back();
    }
}
