<?php

namespace App\Services;

enum ProcessExecutionResultStatus
{
    case SUCCESS;
    case FAILED;
    case STOPPED;
}
