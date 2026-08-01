<?php

namespace App\Jobs\Contracts;

interface DispatchableJob
{
    public function handle(): void;

    public function setExecutionId(?int $id): static;

    public function markExecutionAsProcessing(): void;

    public function markExecutionAsCompleted(): void;

    public function markExecutionAsFailed(): void;
}
