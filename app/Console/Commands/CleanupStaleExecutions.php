<?php

namespace App\Console\Commands;

use App\ExecutionStatus;
use App\Models\Execution;
use App\Services\MediaProbeService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('scan:cleanup')]
#[Description('Delete QUEUED execution records for files that are not actually media files.')]
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
                    $filename = pathinfo($execution->file_path, PATHINFO_FILENAME);

                    // Delete if extension is not in allowlist
                    if (! in_array($ext, $allowedExts, true)) {
                        $execution->delete();
                        $deleted++;
                        $this->line("Deleted [bad ext]: {$execution->file_path}");

                        continue;
                    }

                    // Delete if filename itself contains a dot (double-extension like .d.ts)
                    // Real media files have a single extension: video.ts, movie.mkv
                    if (str_contains($filename, '.')) {
                        $execution->delete();
                        $deleted++;
                        $this->line("Deleted [dotted name]: {$execution->file_path}");
                    }
                }
            });

        $this->info("Deleted {$deleted} stale QUEUED execution(s).");
    }
}
