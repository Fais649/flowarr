<?php

namespace App\Services;

use App\LibraryJobId;
use Symfony\Component\Process\Process;

class ExtractSubtitlesExecutionService extends ProcessExecutionService
{
    private const TEXT_BASED_CODECS = [
        'subrip',
        'srt',
        'ass',
        'ssa',
        'webvtt',
    ];

    protected function buildProcess(): Process
    {
        $extractCommand = array_merge(
            ['./scripts/extract_subtitles.sh', $this->execution->file_path, $this->targetVideoPath()],
            self::TEXT_BASED_CODECS
        );

        $process = new Process($extractCommand);
        $process->setTimeout(null);

        return $process;
    }

    /**
     * Sidecar subtitles must match the file name the video will have after
     * transcoding so players auto-associate them as subtitle tracks.
     */
    private function targetVideoPath(): string
    {
        $transcodeWorker = $this->execution->libraryJob->library->workers
            ->firstWhere('job_type', LibraryJobId::TRANSCODE_MEDIA);

        if ($transcodeWorker === null || $transcodeWorker->replace_original) {
            return $this->execution->file_path;
        }

        try {
            $result = app(MediaProbeService::class)->probe($this->execution->file_path);
            $needsTranscode = $result->isVideo() && ! $result->isTargetVideoEncoding();
        } catch (\Throwable) {
            $needsTranscode = true;
        }

        if (! $needsTranscode) {
            return $this->execution->file_path;
        }

        return preg_replace('/\.[^.]+$/', '_hevc.mkv', $this->execution->file_path);
    }
}
