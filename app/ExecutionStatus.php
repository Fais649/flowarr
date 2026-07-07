<?php

namespace App;

enum ExecutionStatus: string
{
    case QUEUED = 'queued';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case STOPPED = 'stopped';
    case PAUSED = 'paused';
    case FAILED = 'failed';
}
