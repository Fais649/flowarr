<?php

namespace App\Jobs;

use App\MediaJobQueue;
use App\Services\ExtractSubtitlesExecutionService;
use Illuminate\Queue\Attributes\Queue;

#[Queue(queue: MediaJobQueue::EXTRACT_SUBTITLES)]
class ExtractSubtitles extends ExecutionJob
{
    protected function service(): string
    {
        return ExtractSubtitlesExecutionService::class;
    }
}
