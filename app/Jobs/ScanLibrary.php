<?php

namespace App\Jobs;

use App\LibraryStatus;
use App\Models\Library;
use App\OrchestrateJobQueue;
use App\Services\ScannerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Support\Facades\Log;

#[Queue(queue: OrchestrateJobQueue::SCAN_LIBRARIES)]
class ScanLibrary implements ShouldQueue
{
    use Queueable;

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
