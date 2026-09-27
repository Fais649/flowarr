<?php

namespace App\Http\Resources;

use App\Models\Library;
use App\Models\Worker;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Library
 */
class LibraryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'base_path' => $this->base_path,
            'status' => $this->status->value,
            'scan_interval' => $this->scan_interval,
            'last_scan' => $this->last_scan?->toIso8601String(),
            'jobs' => $this->whenLoaded('workers', fn () => $this->workers
                ->map(fn (Worker $worker): array => [
                    'worker_id' => $worker->id,
                    'job_type' => $worker->job_type?->value,
                    'enabled' => $worker->enabled,
                ])->all()),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
