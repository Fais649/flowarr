<?php

namespace App\Services;

use App\ExecutionStatus;
use App\LibraryJobId;
use App\Models\Execution;
use App\Models\Library;
use App\Models\LibraryJob;
use App\Models\Worker;
use Illuminate\Support\Facades\Log;
use SplFileInfo;

class ScannerService
{
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
        '@eaDir',
        '.Trash',
        '.Trashes',
        '$RECYCLE.BIN',
    ];

    public function __construct(
        private MediaProbeService $probeService,
    ) {}

    /**
     * Walk a library and queue an execution for every file that needs work.
     *
     * @return int number of executions queued
     */
    public function scan(Library $library): int
    {
        $basePath = $library->base_path;

        if (! is_dir($basePath) || ! is_readable($basePath)) {
            Log::warning("Library path not accessible: {$basePath}");

            return 0;
        }

        $workers = $library->workers()->where('enabled', true)->get();

        if ($workers->isEmpty()) {
            return 0;
        }

        /** @var array<string, array{library_job: LibraryJob, worker: Worker}> $jobMap */
        $jobMap = [];
        foreach ($workers as $worker) {
            $jobType = $worker->job_type;

            if (! $jobType instanceof LibraryJobId || isset($jobMap[$jobType->value])) {
                continue;
            }

            $jobMap[$jobType->value] = [
                'library_job' => $library->libraryJobs()->firstOrCreate(['job_id' => $jobType]),
                'worker' => $worker,
            ];
        }

        $latestExecutions = $this->latestExecutions(array_values(array_map(fn (array $entry) => $entry['library_job']->id, $jobMap)));
        $queued = 0;

        foreach ($this->collectMediaFiles($basePath) as $file) {
            $filePath = $file->getPathname();
            $size = (int) $file->getSize();
            $mtime = (int) $file->getMTime();
            $probe = null;

            foreach ($jobMap as $entry) {
                $libraryJob = $entry['library_job'];
                $jobId = $libraryJob->job_id;
                $existing = $latestExecutions[$libraryJob->id][$filePath] ?? null;

                if ($existing !== null && $this->isAlreadyHandled($existing, $size, $mtime)) {
                    continue;
                }

                try {
                    if ($this->isJobNeededForFile($filePath, $jobId, $probe)) {
                        $this->dispatchJob($filePath, $size, $mtime, $libraryJob, $entry['worker']);
                        $queued++;
                    }
                } catch (\Throwable $e) {
                    Log::warning("Skipping file {$filePath} for job {$jobId->value}: {$e->getMessage()}");
                }
            }
        }

        return $queued;
    }

    /**
     * An existing execution suppresses a new one while it is still active,
     * when it was stopped by the user, or when it already ran against the
     * file as it is now. Files that changed since (e.g. re-downloaded) are
     * evaluated again, which also gives failed files another chance.
     */
    private function isAlreadyHandled(Execution $existing, int $size, int $mtime): bool
    {
        if ($existing->status->isActive() || $existing->status === ExecutionStatus::STOPPED) {
            return true;
        }

        return $existing->matchesFingerprint($size, $mtime);
    }

    /**
     * @param  list<int>  $libraryJobIds
     * @return array<int, array<string, Execution>> library job id => file path => latest execution
     */
    private function latestExecutions(array $libraryJobIds): array
    {
        $latest = [];

        Execution::whereIn('library_job_id', $libraryJobIds)
            ->select(['id', 'library_job_id', 'file_path', 'file_size', 'file_mtime', 'status'])
            ->orderBy('id')
            ->each(function (Execution $execution) use (&$latest): void {
                $latest[$execution->library_job_id][$execution->file_path] = $execution;
            });

        return $latest;
    }

    /**
     * @return iterable<SplFileInfo>
     */
    private function collectMediaFiles(string $dir): iterable
    {
        $allowedExts = array_merge(
            MediaProbeService::VIDEO_EXTENSIONS,
            MediaProbeService::SUBTITLE_EXTENSIONS,
        );

        $directory = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS);
        $filter = new \RecursiveCallbackFilterIterator($directory, function (SplFileInfo $file): bool {
            if ($file->isDir()) {
                return ! in_array($file->getFilename(), self::EXCLUDED_DIRS, true)
                    && ! str_starts_with($file->getFilename(), '.');
            }

            return true;
        });

        foreach (new \RecursiveIteratorIterator($filter) as $file) {
            /** @var SplFileInfo $file */
            if (! $file->isFile() || ! in_array(strtolower($file->getExtension()), $allowedExts, true)) {
                continue;
            }

            if ($this->isIgnoredFileName($file->getFilename())) {
                continue;
            }

            yield $file;
        }
    }

    /**
     * Hidden files (including macOS "._" resource forks), Flowarr's own
     * in-progress temp files and TypeScript declaration files (".d.ts").
     */
    private function isIgnoredFileName(string $filename): bool
    {
        return str_starts_with($filename, '.')
            || str_contains($filename, '.tmp.')
            || str_ends_with(strtolower($filename), '.d.ts');
    }

    private function isJobNeededForFile(string $filePath, LibraryJobId $jobId, ?MediaProbeResult &$probe): bool
    {
        return match ($jobId) {
            LibraryJobId::TRANSCODE_MEDIA => $this->needsTranscode($filePath, $probe),
            LibraryJobId::EXTRACT_SUBTITLES => $this->hasExtractableSubtitles($filePath, $probe),
            LibraryJobId::CONVERT_SUBTITLE => $this->needsSubtitleConversion($filePath),
        };
    }

    private function needsTranscode(string $filePath, ?MediaProbeResult &$probe): bool
    {
        if (! $this->isVideoFile($filePath)) {
            return false;
        }

        $result = $probe ??= $this->probe($filePath);

        if ($result === null || ! $result->isVideo()) {
            return false;
        }

        if (config('services.ffmpeg.max_bitrate') > 0 || config('services.ffmpeg.max_width') > 0 || config('services.ffmpeg.max_height') > 0) {
            return $result->exceedsTranscodeLimits();
        }

        return ! $result->isTargetVideoEncoding();
    }

    private function hasExtractableSubtitles(string $filePath, ?MediaProbeResult &$probe): bool
    {
        if (! $this->isVideoFile($filePath)) {
            return false;
        }

        $result = $probe ??= $this->probe($filePath);

        return $result !== null && $result->isVideo() && $result->hasTextSubtitles();
    }

    private function needsSubtitleConversion(string $filePath): bool
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if (! in_array($ext, MediaProbeService::SUBTITLE_EXTENSIONS, true)) {
            return false;
        }

        // Never clobber an existing SRT with the same name.
        $target = preg_replace('/\.[^.\/]+$/', '.'.MediaProbeService::TARGET_SUBTITLE_EXTENSION, $filePath);

        return ! file_exists($target);
    }

    private function isVideoFile(string $filePath): bool
    {
        return in_array(strtolower(pathinfo($filePath, PATHINFO_EXTENSION)), MediaProbeService::VIDEO_EXTENSIONS, true);
    }

    private function probe(string $filePath): ?MediaProbeResult
    {
        try {
            return $this->probeService->probe($filePath);
        } catch (\Throwable $e) {
            Log::warning("Probe failed for {$filePath}: {$e->getMessage()}");

            return null;
        }
    }

    private function dispatchJob(string $filePath, int $size, int $mtime, LibraryJob $libraryJob, Worker $worker): void
    {
        $execution = Execution::create([
            'library_job_id' => $libraryJob->id,
            'worker_id' => $worker->id,
            'file_path' => $filePath,
            'file_size' => $size,
            'file_mtime' => $mtime,
            'status' => ExecutionStatus::QUEUED,
        ]);

        try {
            $libraryJob->job_id->dispatch($execution);
        } catch (\Throwable $e) {
            Log::error("Failed to dispatch job for {$filePath}: {$e->getMessage()}");
            $execution->update([
                'status' => ExecutionStatus::FAILED,
                'finished_at' => now(),
                'message' => "Failed to queue job: {$e->getMessage()}",
            ]);
        }
    }
}
