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
     * Large libraries take a while to walk and probe.
     */
    public int $timeout = 0;

    public int $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly int $libraryId,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ScannerService $scanner): void
    {
        // Claim the library atomically so the scheduler and a manual
        // "Scan now" never scan the same library at the same time.
        $claimed = Library::whereKey($this->libraryId)
            ->where('status', '!=', LibraryStatus::SCANNING)
            ->update(['status' => LibraryStatus::SCANNING, 'updated_at' => now()]);

        if ($claimed === 0) {
            Log::info("ScanLibrary: library {$this->libraryId} is missing or already being scanned");

            return;
        }

        $library = Library::findOrFail($this->libraryId);

        try {
            $queued = $scanner->scan($library);
            Log::info("ScanLibrary: library {$this->libraryId} scanned, {$queued} execution(s) queued");
        } catch (\Throwable $e) {
            Log::error("ScanLibrary: library {$this->libraryId} scan failed: {$e->getMessage()}");
        } finally {
            Library::whereKey($this->libraryId)->update([
                'status' => LibraryStatus::PENDING,
                'last_scan' => now(),
            ]);
        }
    }
}
