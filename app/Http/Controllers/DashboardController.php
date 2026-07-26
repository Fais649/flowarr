<?php

namespace App\Http\Controllers;

use App\ExecutionStatus;
use App\Models\Execution;
use App\Models\Library;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $libraryCount = Library::count();
        $pendingExecutions = Execution::where('status', ExecutionStatus::QUEUED)->count();
        $failedToday = Execution::where('status', ExecutionStatus::FAILED)
            ->whereDate('created_at', today())
            ->count();
        $processingCount = Execution::where('status', ExecutionStatus::PROCESSING)->count();

        $recentExecutions = Execution::with('libraryJob.library')
            ->orderBy('created_at', 'desc')
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

        $libraries = Library::with('libraryJobs')->orderBy('created_at', 'desc')->get()->map(fn (Library $l) => [
            'id' => $l->id,
            'base_path' => $l->base_path,
            'status' => $l->status,
            'enabled_jobs' => $l->libraryJobs->count(),
            'last_scan' => $l->last_scan,
        ]);

        return Inertia::render('dashboard', [
            'metrics' => [
                'libraryCount' => $libraryCount,
                'pendingExecutions' => $pendingExecutions,
                'failedToday' => $failedToday,
                'processingCount' => $processingCount,
            ],
            'recentExecutions' => $recentExecutions,
            'libraries' => $libraries,
        ]);
    }
}
