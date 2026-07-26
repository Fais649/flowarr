<?php

namespace App\Services;

use App\ExecutionStatus;
use App\LibraryJobId;
use App\Models\Execution;
use App\Models\Library;
use App\Models\LibraryJob;
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

        $enabledJobs = $library->libraryJobs;
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
            if ($file->isFile()) {
                $ext = strtolower($file->getExtension());
                if (in_array($ext, $allowedExts)) {
                    $files[] = $file->getPathname();
                }
            }
        }

        return $files;
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
        $result = $this->probeService->probe($filePath);

        return ! $result->isTargetVideoEncoding();
    }

    private function hasEmbeddedSubtitles(string $filePath): bool
    {
        $result = $this->probeService->probe($filePath);

        return $result->hasEmbeddedSubtitles();
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
            ->whereIn('status', [ExecutionStatus::QUEUED, ExecutionStatus::PROCESSING])
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
