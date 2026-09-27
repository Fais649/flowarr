<?php

namespace App\Http\Controllers\Api;

use App\ExecutionStatus;
use App\Http\Controllers\Controller;
use App\Models\Execution;
use App\Models\Library;
use App\Models\Worker;
use App\Services\ProcessingGate;
use Illuminate\Http\JsonResponse;

class StatusController extends Controller
{
    public function show(ProcessingGate $gate): JsonResponse
    {
        $counts = Execution::selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return response()->json([
            'processing' => $gate->summary(),
            'executions' => collect(ExecutionStatus::cases())
                ->mapWithKeys(fn (ExecutionStatus $status) => [$status->value => (int) ($counts[$status->value] ?? 0)]),
            'libraries' => Library::count(),
            'workers' => Worker::orderBy('id')->get(['id', 'name', 'job_type', 'concurrency', 'enabled', 'replace_original']),
        ]);
    }

    public function pause(ProcessingGate $gate): JsonResponse
    {
        $gate->pause();

        return response()->json(['processing' => $gate->summary()]);
    }

    public function resume(ProcessingGate $gate): JsonResponse
    {
        $gate->resume();

        return response()->json(['processing' => $gate->summary()]);
    }
}
