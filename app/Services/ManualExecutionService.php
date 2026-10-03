<?php

namespace App\Services;

use App\ExecutionStatus;
use App\LibraryJobId;
use App\Models\Execution;
use App\Models\Library;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManualExecutionService
{
    public function resolveFile(Library $library, string $path, ?LibraryJobId $job = null): string
    {
        $root = realpath($library->base_path);
        $resolved = realpath($path);
        if ($root === false || $resolved === false || ! str_starts_with($resolved, rtrim($root, '/').'/') || ! is_file($resolved) || ! is_readable($resolved) || is_link($path)) {
            throw ValidationException::withMessages(['files' => 'Select readable media files inside this library.']);
        }
        $extensions = $job === LibraryJobId::CONVERT_SUBTITLE ? MediaProbeService::SUBTITLE_EXTENSIONS
            : ($job === null ? [...MediaProbeService::VIDEO_EXTENSIONS, ...MediaProbeService::SUBTITLE_EXTENSIONS] : MediaProbeService::VIDEO_EXTENSIONS);
        if (! in_array(strtolower(pathinfo($resolved, PATHINFO_EXTENSION)), $extensions, true) || str_starts_with(basename($resolved), '.') || str_contains(basename($resolved), '.tmp.')) {
            throw ValidationException::withMessages(['files' => 'The selected file does not support this operation.']);
        }

        return $resolved;
    }

    public function enqueue(Library $library, LibraryJobId $jobId, array $files, bool $now): int
    {
        $worker = $library->workers()->where('job_type', $jobId)->where('enabled', true)->first();
        if ($worker === null) {
            throw ValidationException::withMessages(['job_id' => 'Enable this operation on the library and its worker first.']);
        }
        $paths = array_unique(array_map(fn (string $path) => $this->resolveFile($library, $path, $jobId), $files));

        return DB::transaction(function () use ($library, $jobId, $worker, $paths, $now): int {
            // Serializes manual submissions for this library, including double-clicks.
            Library::whereKey($library->id)->lockForUpdate()->firstOrFail();
            $libraryJob = $library->libraryJobs()->firstOrCreate(['job_id' => $jobId]);
            $count = 0;
            foreach ($paths as $path) {
                $busy = Execution::where('file_path', $path)->where(function ($query) {
                    $query->whereIn('status', ExecutionStatus::active())->orWhere('replacement_status', 'pending');
                })->exists();
                if ($busy) {
                    continue;
                }
                clearstatcache(true, $path);
                $execution = Execution::create([
                    'library_job_id' => $libraryJob->id, 'worker_id' => $worker->id,
                    'file_path' => $path, 'file_size' => filesize($path), 'file_mtime' => filemtime($path),
                    'status' => ExecutionStatus::QUEUED,
                ]);
                $class = $jobId->getJobClass();
                $job = new $class($execution);
                if ($now) {
                    $job->onQueue(match ($jobId) {
                        LibraryJobId::TRANSCODE_MEDIA => 'transcode-media-now',
                        LibraryJobId::EXTRACT_SUBTITLES => 'extract-subtitles-now',
                        LibraryJobId::CONVERT_SUBTITLE => 'convert-subtitle-now',
                    });
                }
                dispatch($job->afterCommit());
                $count++;
            }

            return $count;
        });
    }
}
