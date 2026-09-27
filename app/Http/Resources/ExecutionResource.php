<?php

namespace App\Http\Resources;

use App\Models\Execution;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Execution
 */
class ExecutionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'job_type' => $this->libraryJob?->job_id?->value,
            'library_id' => $this->libraryJob?->library_id,
            'worker_id' => $this->worker_id,
            'file_path' => $this->file_path,
            'progress' => $this->progress,
            'message' => $this->message,
            'output' => $this->when($request->routeIs('api.executions.show'), fn () => $this->output),
            'started_at' => $this->started_at?->toIso8601String(),
            'finished_at' => $this->finished_at?->toIso8601String(),
            'duration_seconds' => $this->durationSeconds(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
