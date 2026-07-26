<?php

namespace App\Console\Commands;

use App\ExecutionStatus;
use App\Models\Execution;
use App\Services\MediaProbeService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('scan:cleanup')]
#[Description('Delete QUEUED execution records for files with non-media extensions.')]
class CleanupStaleExecutions extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $allowedExts = array_merge(
            MediaProbeService::VIDEO_EXTENSIONS,
            MediaProbeService::SUBTITLE_EXTENSIONS,
        );

        $deleted = 0;

        Execution::where('status', ExecutionStatus::QUEUED)
            ->chunk(100, function ($executions) use ($allowedExts, &$deleted): void {
                foreach ($executions as $execution) {
                    $ext = strtolower(pathinfo($execution->file_path, PATHINFO_EXTENSION));

                    if (! in_array($ext, $allowedExts, true)) {
                        $execution->delete();
                        $deleted++;
                        $this->line("Deleted: {$execution->file_path}");
                    }
                }
            });

        $this->info("Deleted {$deleted} stale QUEUED execution(s) with non-media extensions.");
    }
}
