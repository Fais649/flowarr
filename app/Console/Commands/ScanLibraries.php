<?php

namespace App\Console\Commands;

use App\LibraryStatus;
use App\Models\Library;
use App\Services\ScannerService;
use App\Settings;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('scan:libraries')]
#[Description('Scan all libraries that are due for scan and dispatch jobs')]
class ScanLibraries extends Command
{
    public function handle(ScannerService $scanner): void
    {
        // Reset any libraries stuck in SCANNING for more than 5 minutes
        // (left over from a previously-crashed scan)
        Library::where('status', LibraryStatus::SCANNING)
            ->where('updated_at', '<', now()->subMinutes(5))
            ->update(['status' => LibraryStatus::PENDING_SCAN]);

        // Log PENDING_SCAN libraries excluded by dueForScan for debugging
        $pendingScanWithoutJobsOrWorkers = Library::where('status', LibraryStatus::PENDING_SCAN)
            ->where(function ($q) {
                $q->whereDoesntHave('libraryJobs')
                    ->whereDoesntHave('workers');
            })
            ->get();
        foreach ($pendingScanWithoutJobsOrWorkers as $lib) {
            Log::info("Library {$lib->id} (PENDING_SCAN) not due for scan: no libraryJobs or workers enabled");
        }

        $libraries = Library::dueForScan()->get();

        if ($libraries->isEmpty()) {
            Log::debug('scan:libraries — no libraries due for scan');

            return;
        }

        $maxConcurrent = Settings::scanConcurrency();

        foreach ($libraries->take($maxConcurrent) as $library) {
            Log::info("Scanning library {$library->id}: {$library->base_path}");

            $library->update(['status' => LibraryStatus::SCANNING]);

            try {
                $scanner->scan($library);
                $library->update([
                    'status' => LibraryStatus::PENDING,
                    'last_scan' => now(),
                ]);
            } catch (\Throwable $e) {
                Log::error("Scan failed for library {$library->id}: {$e->getMessage()}");
                $library->update(['status' => LibraryStatus::PENDING]);
            }
        }
    }
}
