<?php

namespace App\Console\Commands;

use App\ExecutionStatus;
use App\Models\Execution;
use App\Services\ExecutionNotifier;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('executions:reap {--minutes=5 : Minutes without a heartbeat before an execution is considered lost}')]
#[Description('Mark processing executions whose worker stopped sending heartbeats (crash, container restart) as failed')]
class ReapStaleExecutions extends Command
{
    public function handle(ExecutionNotifier $notifier): void
    {
        $cutoff = now()->subMinutes((int) $this->option('minutes'));

        $stale = Execution::whereIn('status', [ExecutionStatus::PROCESSING, ExecutionStatus::PAUSED])
            ->where(function ($query) use ($cutoff): void {
                $query->where('heartbeat_at', '<', $cutoff)
                    ->orWhere(fn ($q) => $q->whereNull('heartbeat_at')->where('updated_at', '<', $cutoff));
            })
            ->get();

        foreach ($stale as $execution) {
            $execution->update([
                'status' => ExecutionStatus::FAILED,
                'finished_at' => now(),
                'message' => 'The worker processing this execution stopped responding.',
            ]);

            $notifier->executionFinished($execution);
        }

        $this->info("Marked {$stale->count()} stale execution(s) as failed.");
    }
}
