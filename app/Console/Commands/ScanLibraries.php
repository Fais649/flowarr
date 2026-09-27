<?php

namespace App\Console\Commands;

use App\Jobs\ScanLibrary;
use App\LibraryStatus;
use App\Models\Library;
use App\Settings;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('scan:libraries')]
#[Description('Scan all libraries that are due for scan and dispatch jobs')]
class ScanLibraries extends Command
{
    public function handle(): void
    {
        // Reset any libraries stuck in SCANNING for more than 5 minutes
        // (left over from a previously-crashed scan)
        Library::where('status', LibraryStatus::SCANNING)
            ->where('updated_at', '<', now()->subMinutes(5))
            ->update(['status' => LibraryStatus::PENDING_SCAN]);

        $libraries = Library::dueForScan()->get();

        if ($libraries->isEmpty()) {
            Log::debug('scan:libraries — no libraries due for scan');

            return;
        }

        foreach ($libraries->take(Settings::scanConcurrency()) as $library) {
            Log::info("Scanning library {$library->id}: {$library->base_path}");

            ScanLibrary::dispatchSync($library->id);
        }
    }
}
