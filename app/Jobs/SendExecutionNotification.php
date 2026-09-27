<?php

namespace App\Jobs;

use App\Models\Execution;
use App\OrchestrateJobQueue;
use App\Services\ExecutionNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Queue;

#[Queue(queue: OrchestrateJobQueue::NOTIFICATIONS)]
class SendExecutionNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var list<int>
     */
    public array $backoff = [10, 60];

    public function __construct(public readonly int $executionId) {}

    public function handle(ExecutionNotifier $notifier): void
    {
        $execution = Execution::find($this->executionId);

        if ($execution === null) {
            return;
        }

        $notifier->send($execution);
    }
}
