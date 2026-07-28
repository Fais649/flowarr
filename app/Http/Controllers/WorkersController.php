<?php

namespace App\Http\Controllers;

use App\ExecutionStatus;
use App\Http\Requests\StoreWorkerRequest;
use App\Http\Requests\UpdateWorkerRequest;
use App\Models\Execution;
use App\Models\LibraryJob;
use App\Models\Worker;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class WorkersController extends Controller
{
    public function index(): Response
    {
        $workers = Worker::orderBy('created_at', 'desc')->get();

        return Inertia::render('workers/index', [
            'workers' => $workers,
        ]);
    }

    public function show(Worker $worker): Response
    {
        return Inertia::render('workers/[id]/index', [
            'worker' => $worker,
        ]);
    }

    public function store(StoreWorkerRequest $request): RedirectResponse
    {
        Worker::create($request->validated());

        return redirect()->route('workers.index');
    }

    public function update(UpdateWorkerRequest $request, Worker $worker): RedirectResponse
    {
        $worker->update($request->validated());

        return redirect()->back();
    }

    public function destroy(Worker $worker): RedirectResponse
    {
        $worker->delete();

        return redirect()->route('workers.index');
    }

    public function start(Worker $worker): RedirectResponse
    {
        $libraryJobIds = LibraryJob::where('job_id', $worker->job_type)->pluck('id');

        Execution::whereIn('library_job_id', $libraryJobIds)
            ->whereIn('status', [ExecutionStatus::QUEUED, ExecutionStatus::PAUSED])
            ->update(['status' => ExecutionStatus::PROCESSING]);

        return redirect()->back();
    }

    public function pause(Worker $worker): RedirectResponse
    {
        $libraryJobIds = LibraryJob::where('job_id', $worker->job_type)->pluck('id');

        Execution::whereIn('library_job_id', $libraryJobIds)
            ->where('status', ExecutionStatus::PROCESSING)
            ->update(['status' => ExecutionStatus::PAUSED]);

        return redirect()->back();
    }

    public function resume(Worker $worker): RedirectResponse
    {
        $libraryJobIds = LibraryJob::where('job_id', $worker->job_type)->pluck('id');

        Execution::whereIn('library_job_id', $libraryJobIds)
            ->where('status', ExecutionStatus::PAUSED)
            ->update(['status' => ExecutionStatus::PROCESSING]);

        return redirect()->back();
    }

    public function stop(Worker $worker): RedirectResponse
    {
        $libraryJobIds = LibraryJob::where('job_id', $worker->job_type)->pluck('id');

        Execution::whereIn('library_job_id', $libraryJobIds)
            ->whereIn('status', [ExecutionStatus::QUEUED, ExecutionStatus::PROCESSING, ExecutionStatus::PAUSED])
            ->update(['status' => ExecutionStatus::STOPPED]);

        return redirect()->back();
    }

    public function startAll(): RedirectResponse
    {
        Execution::whereIn('status', [ExecutionStatus::QUEUED, ExecutionStatus::PAUSED])
            ->update(['status' => ExecutionStatus::PROCESSING]);

        return redirect()->back();
    }

    public function pauseAll(): RedirectResponse
    {
        Execution::where('status', ExecutionStatus::PROCESSING)
            ->update(['status' => ExecutionStatus::PAUSED]);

        return redirect()->back();
    }

    public function resumeAll(): RedirectResponse
    {
        Execution::where('status', ExecutionStatus::PAUSED)
            ->update(['status' => ExecutionStatus::PROCESSING]);

        return redirect()->back();
    }

    public function stopAll(): RedirectResponse
    {
        Execution::whereIn('status', [ExecutionStatus::QUEUED, ExecutionStatus::PROCESSING, ExecutionStatus::PAUSED])
            ->update(['status' => ExecutionStatus::STOPPED]);

        return redirect()->back();
    }
}
