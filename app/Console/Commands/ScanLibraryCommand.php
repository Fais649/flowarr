<?php

namespace App\Console\Commands;

use App\ExecutionStatus;
use App\LibraryJobId;
use App\Models\Execution;
use App\Models\Library;
use App\Models\LibraryJob;
use App\Services\MediaProbeResult;
use App\Services\MediaProbeService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Symfony\Component\Finder\Finder;

#[Signature('app:scan-library-command')]
#[Description('Scans the media library and enqueues any files that need processing.')]
class ScanLibraryCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(MediaProbeService $probeService): void
    {
        foreach (Library::dueForScan()->get() as $library) {
            $this->processLibrary($library, $probeService);
        }

    }

    public function processLibrary(Library $library, MediaProbeService $probeService): void
    {
        $enabledJobs = $library->libraryJobs;
        if ($enabledJobs->isEmpty()) {
            $this->info('No jobs configured for library {$library->id}. Skipping.');

            return;
        }

        $files = Finder::create()
            ->files()
            ->in($library->base_path)
            ->ignoreUnreadableDirs()
            ->ignoreVCS(true);

        foreach ($files as $file) {
            $path = $file->getRealPath();
            $result = $probeService->probe($path);
            $this->dispatchForResult($enabledJobs, $result, $path);
        }
        $library->update(['last_scan' => now()]);
    }

    /**
     * @param  Collection<int, LibraryJob>  $enabledJobs
     */
    private function dispatchForResult(Collection $enabledJobs, MediaProbeResult $result, string $path): void
    {
        foreach ($this->getRequiredJobs($enabledJobs, $result) as $jobType) {
            $this->tryDispatch($enabledJobs, $jobType, $path);
        }
    }

    /**
     * @param  Collection<int, LibraryJob>  $enabledJobs
     * @return array<LibraryJobId>
     */
    public function getRequiredJobs(Collection $enabledJobs, MediaProbeResult $result): array
    {
        $jobs = [];
        if ($result->isVideo()) {
            if (! $result->isTargetVideoEncoding() && $this->isJobEnabled($enabledJobs, LibraryJobId::TRANSCODE_MEDIA)) {
                $jobs[] = LibraryJobId::TRANSCODE_MEDIA;
            }

            if ($result->hasEmbeddedSubs() && $this->isJobEnabled($enabledJobs, LibraryJobId::EXTRACT_SUBTITLES)) {
                $jobs[] = LibraryJobId::EXTRACT_SUBTITLES;
            }

            return $jobs;
        }

        if (! $result->isTargetSubtitleExtension() && $this->isJobEnabled($enabledJobs, LibraryJobId::CONVERT_SUBTITLE)) {
            $jobs[] = LibraryJobId::CONVERT_SUBTITLE;
        }

        return $jobs;
    }

    /**
     * @param  Collection<int, LibraryJob>  $enabledJobs
     */
    private function isJobEnabled(Collection $enabledJobs, LibraryJobId $jobId): bool
    {
        return $enabledJobs->contains(
            fn (LibraryJob $libraryJob) => $libraryJob->job_id === $jobId
        );
    }

    /**
     * @param  Collection<int, LibraryJob>  $enabledJobs
     */
    private function tryDispatch(Collection $enabledJobs, LibraryJobId $jobType, string $path): void
    {
        $libraryJob = $enabledJobs->first(fn (LibraryJob $libraryJob) => $libraryJob->job_id === $jobType);
        $exists = Execution::where('library_job_id', $libraryJob->id)
            ->where('file_path', $path)
            ->whereIn('status', [ExecutionStatus::QUEUED, ExecutionStatus::PROCESSING])
            ->exists();

        if ($exists) {
            return;
        }

        Execution::create([
            'library_job_id' => $libraryJob->id,
            'file_path' => $path,
            'status' => ExecutionStatus::QUEUED,
        ]);

        $jobClass = $jobType->getJobClass();
        $jobClass::dispatch($path);
    }
}
