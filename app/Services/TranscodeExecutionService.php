<?php

namespace App\Services;

use App\ExecutionStatus;
use Symfony\Component\Process\Process;
use Throwable;

class TranscodeExecutionService extends ProcessExecutionService
{
    /** Tonemaps HDR (PQ/HLG) to SDR bt709 so HEVC output plays correctly on SDR clients. */
    public const HDR_FILTER = 'zscale=t=linear:npl=100,format=gbrpf32le,zscale=p=bt709,tonemap=tonemap=hable:desat=0,zscale=t=bt709:m=bt709:r=tv,format=yuv420p';

    private ?array $replacement = null;

    protected function buildProcess(): Process
    {
        $library = $this->execution->libraryJob?->library;
        $policy = app(OriginalReplacement::class)->policy($library, (bool) $this->execution->worker?->replace_original);
        $legacyImmediate = $library?->original_handling === null && $policy === 'immediate';
        $replaceOriginal = $legacyImmediate ? 'true' : 'false';
        if ($policy !== 'keep' && ! $legacyImmediate) {
            clearstatcache(true, $this->execution->file_path);
            $this->replacement = [
                'policy' => $policy, 'start' => $library?->replacement_start, 'end' => $library?->replacement_end,
                'size' => filesize($this->execution->file_path), 'mtime' => filemtime($this->execution->file_path),
                'inode' => fileinode($this->execution->file_path), 'output' => TranscodeOutput::pathFor($this->execution->file_path, false),
            ];
        }

        return new Process([
            $this->script('transcode_media.sh'),
            $this->execution->file_path,
            $replaceOriginal,
            HardwareCapabilities::configuredMode(),
            $this->videoFilter(),
            (string) config('services.ffmpeg.max_bitrate', 0),
            $this->replacement !== null ? 'true' : 'false',
        ], base_path());
    }

    protected function finish(ExecutionStatus $status, ProcessExecutionResultStatus $result, string $message, array $attributes = []): ProcessExecutionResult
    {
        if ($status === ExecutionStatus::COMPLETED && $this->replacement !== null) {
            $this->replacement['hash'] = hash_file('sha256', $this->replacement['output']);
            $attributes['replacement'] = $this->replacement;
            $attributes['replacement_status'] = 'pending';
        }
        $finished = parent::finish($status, $result, $message, $attributes);
        if ($status === ExecutionStatus::COMPLETED && $this->replacement !== null) {
            app(OriginalReplacement::class)->replace($this->execution);
            if ($this->execution->replacement_status === 'pending') {
                $this->execution->update(['message' => 'Transcoded; waiting for original replacement policy.']);
            }
        }

        return $finished;
    }

    private function videoFilter(): string
    {
        $override = config('services.ffmpeg.video_filter');

        try {
            $probe = app(MediaProbeService::class)->probe($this->execution->file_path);
            $this->durationSeconds = $probe->duration();
            $isHdr = $probe->isHdr();
        } catch (Throwable) {
            $isHdr = false;
        }

        $filter = is_string($override) && $override !== '' ? $override : ($isHdr ? self::HDR_FILTER : '');
        $width = (int) config('services.ffmpeg.max_width');
        $height = (int) config('services.ffmpeg.max_height');
        if ($width > 0 && $height > 0) {
            $scale = "scale=w='min({$width},iw)':h='min({$height},ih)':force_original_aspect_ratio=decrease:force_divisible_by=2";
            $filter = $filter !== '' ? $filter.','.$scale : $scale;
        }

        return $filter;
    }
}
