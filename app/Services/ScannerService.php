<?php

namespace App\Services;

use App\ExecutionStatus;
use App\LibraryJobId;
use App\Models\Execution;
use App\Models\Library;
use App\Models\LibraryJob;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ScannerService
{
    public function __construct(
        private MediaProbeService $probeService,
    ) {}

    public function scan(Library $library): void
    {
        $basePath = $library->base_path;

        if (! is_dir($basePath) || ! is_readable($basePath)) {
            Log::warning("Library path not accessible: {$basePath}");

            return;
        }

        /** @var Collection<int, LibraryJob> */
        $enabledJobs = new Collection;
        if (! $library->workers()->exists()) {
            return;
        }

        $workers = $library->workers;
        foreach ($workers as $worker) {
            $jobType = $worker->job_type;
            if ($jobType) {
                $enabledJobs->push(
                    $library->libraryJobs()->firstOrCreate([
                        'job_id' => $jobType,
                    ])
                );
            }
        }

        $files = $this->collectMediaFiles($basePath);

        foreach ($files as $filePath) {
            foreach ($enabledJobs as $libraryJob) {
                $jobId = $libraryJob->job_id;

                try {
                    if ($this->isJobNeededForFile($filePath, $jobId)) {
                        if ($this->hasExistingExecution($filePath, $libraryJob->id)) {
                            continue;
                        }

                        $this->dispatchJob($filePath, $libraryJob, $jobId);
                    }
                } catch (\Throwable $e) {
                    Log::warning("Skipping file {$filePath} for job {$jobId->value}: {$e->getMessage()}");
                }
            }
        }
    }

    /** Directory basenames whose contents are never media files. */
    private const EXCLUDED_DIRS = [
        'node_modules',
        '.git',
        'vendor',
        '.bun',
        '.npm',
        '.yarn',
        '.pnpm',
        '__pycache__',
        '.cache',
    ];

    private function collectMediaFiles(string $dir): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
        );

        $allowedExts = array_merge(
            MediaProbeService::VIDEO_EXTENSIONS,
            MediaProbeService::SUBTITLE_EXTENSIONS,
        );

        foreach ($iterator as $file) {
            // Skip files inside excluded directories (node_modules, .git, vendor, etc.)
            if ($this->isInExcludedDir($file->getPathname())) {
                continue;
            }

            if ($file->isFile()) {
                $ext = strtolower($file->getExtension());
                if (! in_array($ext, $allowedExts)) {
                    continue;
                }

                // Skip files that look like they have a double extension
                // (e.g. .d.ts -> pathinfo gives ext=ts but basename ends in .d)
                $filename = $file->getFilename();
                $nameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);
                if (str_contains($nameWithoutExt, '.')) {
                    continue;
                }

                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    private function isInExcludedDir(string $path): bool
    {
        $parts = explode(DIRECTORY_SEPARATOR, $path);
        foreach ($parts as $part) {
            if (in_array($part, self::EXCLUDED_DIRS, true)) {
                return true;
            }
        }

        return false;
    }

    private function isJobNeededForFile(string $filePath, LibraryJobId $jobId): bool
    {
        return match ($jobId) {
            LibraryJobId::TRANSCODE_MEDIA => $this->needsTranscode($filePath),
            LibraryJobId::EXTRACT_SUBTITLES => $this->hasEmbeddedSubtitles($filePath),
            LibraryJobId::CONVERT_SUBTITLE => $this->needsSubtitleConversion($filePath),
        };
    }

    private function needsTranscode(string $filePath): bool
    {
        try {
            $result = $this->probeService->probe($filePath);
        } catch (\Throwable $e) {
            Log::warning("Probe failed for {$filePath}: {$e->getMessage()}");

            return false;
        }

        // If FFProbe didn't identify video streams, skip
        if (! $result->isVideo()) {
            return false;
        }

        return ! $result->isTargetVideoEncoding();
    }

    private function hasEmbeddedSubtitles(string $filePath): bool
    {
        try {
            $result = $this->probeService->probe($filePath);
        } catch (\Throwable $e) {
            Log::warning("Subtitle probe failed for {$filePath}: {$e->getMessage()}");

            return false;
        }

        // Only check for embedded subs in actual video files
        if (! $result->isVideo()) {
            return false;
        }

        return $result->hasEmbeddedSubs();
    }

    private function needsSubtitleConversion(string $filePath): bool
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        // Only process files with known subtitle extensions
        if (! in_array($ext, MediaProbeService::SUBTITLE_EXTENSIONS)) {
            return false;
        }

        // Probe the file to verify it's actually a subtitle before dispatching
        try {
            $result = $this->probeService->probe($filePath);

            return ! $result->isTargetSubtitleExtension();
        } catch (\Throwable $e) {
            Log::warning("Subtitle probe failed for {$filePath}: {$e->getMessage()}");

            return false;
        }
    }

    private function hasExistingExecution(string $filePath, int $libraryJobId): bool
    {
        return Execution::where('file_path', $filePath)
            ->where('library_job_id', $libraryJobId)
            ->whereNotIn('status', [ExecutionStatus::FAILED])
            ->exists();
    }

    private function dispatchJob(string $filePath, LibraryJob $libraryJob, LibraryJobId $jobId): void
    {
        $execution = Execution::create([
            'library_job_id' => $libraryJob->id,
            'file_path' => $filePath,
            'status' => ExecutionStatus::QUEUED,
        ]);

        try {
            $jobClass = $jobId->getJobClass();
            $job = new $jobClass($filePath);
            $job->setExecutionId($execution->id);
            dispatch($job);
        } catch (\Throwable $e) {
            Log::error("Failed to dispatch job for {$filePath}: {$e->getMessage()}");
        }
    }
}
