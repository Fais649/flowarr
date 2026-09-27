<?php

namespace App\Http\Controllers;

use App\ExecutionStatus;
use App\Models\Execution;
use App\Models\Library;
use App\Services\ProcessingGate;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(ProcessingGate $gate): Response
    {
        $statusCounts = Execution::selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $count = fn (ExecutionStatus $status): int => (int) ($statusCounts[$status->value] ?? 0);

        $failedToday = Execution::where('status', ExecutionStatus::FAILED)
            ->where('finished_at', '>=', today())
            ->count();

        $completedToday = Execution::where('status', ExecutionStatus::COMPLETED)
            ->where('finished_at', '>=', today())
            ->count();

        $processingExecutions = Execution::with('libraryJob.library')
            ->whereIn('status', [ExecutionStatus::PROCESSING, ExecutionStatus::PAUSED])
            ->orderBy('started_at')
            ->get()
            ->map(fn (Execution $e) => [
                'id' => $e->id,
                'file_path' => $e->file_path,
                'status' => $e->status,
                'job_type' => $e->libraryJob?->job_id,
                'library' => $e->libraryJob?->library?->base_path,
                'progress' => $e->progress,
                'message' => $e->message,
                'started_at' => $e->started_at,
                'duration' => $e->durationSeconds(),
            ]);

        $queuedByType = Execution::where('executions.status', ExecutionStatus::QUEUED)
            ->join('library_jobs', 'library_jobs.id', '=', 'executions.library_job_id')
            ->selectRaw('library_jobs.job_id as job_type, count(*) as aggregate')
            ->groupBy('library_jobs.job_id')
            ->pluck('aggregate', 'job_type')
            ->map(fn ($count, $jobType) => ['job_type' => $jobType, 'count' => (int) $count])
            ->values();

        $recentExecutions = Execution::with('libraryJob.library')
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn (Execution $e) => [
                'id' => $e->id,
                'file_path' => $e->file_path,
                'status' => $e->status,
                'library' => $e->libraryJob?->library?->base_path,
                'job_type' => $e->libraryJob?->job_id,
                'created_at' => $e->created_at,
            ]);

        $libraries = Library::withCount('workers')->orderBy('created_at', 'desc')->get()->map(fn (Library $l) => [
            'id' => $l->id,
            'base_path' => $l->base_path,
            'status' => $l->status,
            'enabled_jobs' => $l->workers_count,
            'last_scan' => $l->last_scan,
        ]);

        return Inertia::render('dashboard', [
            'metrics' => [
                'libraryCount' => $libraries->count(),
                'pendingExecutions' => $count(ExecutionStatus::QUEUED),
                'failedToday' => $failedToday,
                'completedToday' => $completedToday,
                'processingCount' => $count(ExecutionStatus::PROCESSING) + $count(ExecutionStatus::PAUSED),
            ],
            'processing' => $gate->summary(),
            'processingExecutions' => $processingExecutions,
            'queuedByType' => $queuedByType,
            'recentExecutions' => $recentExecutions,
            'libraries' => $libraries,
        ]);
    }
}
