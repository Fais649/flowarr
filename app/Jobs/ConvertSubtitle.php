<?php

namespace App\Jobs;

use App\MediaJobQueue;
use App\Services\ConvertSubtitleExecutionService;
use Illuminate\Queue\Attributes\Queue;

#[Queue(queue: MediaJobQueue::CONVERT_SUBTITLE)]
class ConvertSubtitle extends ExecutionJob
{
    protected function service(): string
    {
        return ConvertSubtitleExecutionService::class;
    }
}
