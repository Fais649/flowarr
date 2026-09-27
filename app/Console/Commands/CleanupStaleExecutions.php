<?php

namespace App\Console\Commands;

use App\ExecutionStatus;
use App\Models\Execution;
use App\Services\MediaProbeService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('scan:cleanup')]
#[Description('Delete QUEUED execution records for files that are missing or not media files.')]
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
            ->chunkById(100, function ($executions) use ($allowedExts, &$deleted): void {
                foreach ($executions as $execution) {
                    $ext = strtolower(pathinfo($execution->file_path, PATHINFO_EXTENSION));

                    $reason = match (true) {
                        ! in_array($ext, $allowedExts, true) => 'bad ext',
                        str_ends_with(strtolower($execution->file_path), '.d.ts') => 'declaration file',
                        ! file_exists($execution->file_path) => 'missing file',
                        default => null,
                    };

                    if ($reason !== null) {
                        $execution->delete();
                        $deleted++;
                        $this->line("Deleted [{$reason}]: {$execution->file_path}");
                    }
                }
            });

        $this->info("Deleted {$deleted} stale QUEUED execution(s).");
    }
}
