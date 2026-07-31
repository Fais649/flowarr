<?php

namespace App\Jobs;

use App\LibraryStatus;
use App\MediaJobQueue;
use App\Models\Library;
use App\Services\ScannerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ScanLibraryJob implements ShouldQueue
{
    use Queueable;

    public MediaJobQueue $queue = MediaJobQueue::ORCHESTRATE;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly int $libraryId,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ScannerService $scanner): void
    {
        $library = Library::find($this->libraryId);

        if (! $library) {
            Log::warning("ScanLibraryJob: Library {$this->libraryId} not found");

            return;
        }

        $library->update(['status' => LibraryStatus::SCANNING]);

        try {
            $scanner->scan($library);
            $library->update([
                'status' => LibraryStatus::PENDING,
                'last_scan' => now(),
            ]);
            Log::info("ScanLibraryJob: Library {$this->libraryId} scan complete");
        } catch (\Throwable $e) {
            Log::error("ScanLibraryJob: Library {$this->libraryId} scan failed: {$e->getMessage()}");
            $library->update(['status' => LibraryStatus::PENDING]);
        }
    }
}
