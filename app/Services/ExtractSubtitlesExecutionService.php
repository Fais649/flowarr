<?php

namespace App\Services;

use App\LibraryJobId;
use Symfony\Component\Process\Process;

class ExtractSubtitlesExecutionService extends ProcessExecutionService
{
    protected function buildProcess(): Process
    {
        $stripEmbedded = $this->execution->worker?->replace_original ? 'true' : 'false';

        return new Process([
            $this->script('extract_subtitles.sh'),
            $this->execution->file_path,
            $this->targetVideoPath(),
            $stripEmbedded,
            ...MediaProbeService::TEXT_SUBTITLE_CODECS,
        ], base_path());
    }

    /**
     * Sidecar subtitles must match the file name the video will have after
     * transcoding so players auto-associate them as subtitle tracks.
     */
    private function targetVideoPath(): string
    {
        $library = $this->execution->libraryJob?->library;
        $transcodeWorker = $library?->workers
            ->where('enabled', true)
            ->firstWhere('job_type', LibraryJobId::TRANSCODE_MEDIA);

        if ($transcodeWorker === null) {
            return $this->execution->file_path;
        }

        try {
            $result = app(MediaProbeService::class)->probe($this->execution->file_path);
            $needsTranscode = $result->isVideo() && ! $result->isTargetVideoEncoding();
        } catch (\Throwable) {
            $needsTranscode = false;
        }

        if (! $needsTranscode) {
            return $this->execution->file_path;
        }

        return TranscodeOutput::pathFor($this->execution->file_path, $transcodeWorker->replace_original);
    }
}
