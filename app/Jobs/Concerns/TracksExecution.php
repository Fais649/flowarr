<?php

namespace App\Jobs\Concerns;

use App\ExecutionStatus;
use App\Models\Execution;

trait TracksExecution
{
    protected ?int $executionId = null;

    public function setExecutionId(?int $id): static
    {
        $this->executionId = $id;

        return $this;
    }

    protected function markExecutionAsProcessing(): void
    {
        if ($this->executionId === null) {
            return;
        }

        Execution::where('id', $this->executionId)->update([
            'status' => ExecutionStatus::PROCESSING,
            'started_at' => now(),
        ]);
    }

    protected function markExecutionAsCompleted(): void
    {
        if ($this->executionId === null) {
            return;
        }

        Execution::where('id', $this->executionId)->update([
            'status' => ExecutionStatus::COMPLETED,
            'finished_at' => now(),
        ]);
    }

    protected function markExecutionAsFailed(): void
    {
        if ($this->executionId === null) {
            return;
        }

        Execution::where('id', $this->executionId)->update([
            'status' => ExecutionStatus::FAILED,
            'finished_at' => now(),
        ]);
    }
}
