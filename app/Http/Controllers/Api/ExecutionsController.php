<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExecutionFilterRequest;
use App\Http\Resources\ExecutionResource;
use App\Models\Execution;
use App\Services\ExecutionControl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ExecutionsController extends Controller
{
    public function __construct(private ExecutionControl $control) {}

    public function index(ExecutionFilterRequest $request): AnonymousResourceCollection
    {
        $query = Execution::with('libraryJob');

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('library_id')) {
            $query->whereHas('libraryJob', fn ($q) => $q->where('library_id', $request->integer('library_id')));
        }

        $query->orderBy($request->input('sort', 'created_at'), $request->input('direction', 'desc') === 'asc' ? 'asc' : 'desc')
            ->orderBy('id', 'desc');

        return ExecutionResource::collection($query->paginate($request->integer('per_page', 25))->withQueryString());
    }

    public function show(Execution $execution): ExecutionResource
    {
        return new ExecutionResource($execution->load('libraryJob'));
    }

    public function retry(Execution $execution): JsonResponse
    {
        return $this->respond($execution, $this->control->retry($execution));
    }

    public function pause(Execution $execution): JsonResponse
    {
        return $this->respond($execution, $this->control->pause($execution));
    }

    public function resume(Execution $execution): JsonResponse
    {
        return $this->respond($execution, $this->control->resume($execution));
    }

    public function stop(Execution $execution): JsonResponse
    {
        return $this->respond($execution, $this->control->stop($execution));
    }

    private function respond(Execution $execution, bool $changed): JsonResponse
    {
        return (new ExecutionResource($execution->refresh()->load('libraryJob')))
            ->response()
            ->setStatusCode($changed ? 200 : 409);
    }
}
