<?php

namespace App\Jobs;

use App\MediaJobQueue;
use App\Services\TranscodeExecutionService;
use Illuminate\Queue\Attributes\Queue;

#[Queue(queue: MediaJobQueue::TRANSCODE_MEDIA)]
class TranscodeMedia extends ExecutionJob
{
    protected function service(): string
    {
        return TranscodeExecutionService::class;
    }
}
