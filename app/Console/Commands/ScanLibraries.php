<?php

namespace App\Console\Commands;

use App\LibraryStatus;
use App\Models\Library;
use App\Services\ScannerService;
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
        $libraries = Library::dueForScan()->get();

        if ($libraries->isEmpty()) {
            return;
        }

        foreach ($libraries as $library) {
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
