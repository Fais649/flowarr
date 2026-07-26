<?php

namespace App\Traits;

use App\Models\JobWorkerLimit;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Support\Facades\Cache;

trait HasJobSlot
{
    abstract public static function jobType(): string;

    protected function acquireSlot(int $timeout = 10): ?Lock
    {
        $maxConcurrent = JobWorkerLimit::where('job_type', static::jobType())->value('max_concurrent') ?? 1;
        for ($i = 0; $i < $maxConcurrent; $i++) {
            $lock = Cache::lock('job_slot:'.static::jobType().':'.$i, $timeout);
            if ($lock->get()) {
                return $lock;
            }
        }

        return null;
    }
}
