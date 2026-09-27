<?php

namespace App\Services;

class ProcessExecutionResult
{
    public function __construct(
        public readonly ProcessExecutionResultStatus $status,
        public readonly string $message,
    ) {}

    public function isSuccessful(): bool
    {
        return $this->status === ProcessExecutionResultStatus::SUCCESS;
    }
}
