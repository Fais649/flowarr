<?php

namespace App\Jobs\Concerns;

use App\ExecutionStatus;
use App\Models\Execution;

trait TracksExecution
{
    protected ?int $executionId = null;

    protected ?Execution $execution = null;

    public function setExecutionId(?int $id): static
    {
        $this->executionId = $id;
        $this->execution = Execution::find($id);

        return $this;
    }

    public function markExecutionAsProcessing(): void
    {
        if ($this->executionId === null) {
            return;
        }
        $this->markExecution(
            [
                'status' => ExecutionStatus::PROCESSING,
                'started_at' => now(),
            ]
        );
    }

    public function markExecutionAsCompleted(): void
    {
        if ($this->executionId === null) {
            return;
        }

        $this->markExecution(
            [
                'status' => ExecutionStatus::COMPLETED,
                'finished_at' => now(),
            ]
        );
    }

    public function markExecutionAsFailed(): void
    {
        if ($this->executionId === null) {
            return;
        }

        $this->markExecution(
            [
                'status' => ExecutionStatus::FAILED,
                'finished_at' => now(),
            ]
        );
    }

    protected function markExecution(array $attributes)
    {
        if ($this->execution) {
            $this->execution->update($attributes);

            return;
        }

        Execution::where('id', $this->executionId)->update($attributes);
    }
}
