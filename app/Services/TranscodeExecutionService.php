<?php

namespace App\Services;

use Symfony\Component\Process\Process;
use Throwable;

class TranscodeExecutionService extends ProcessExecutionService
{
    /** Tonemaps HDR (PQ/HLG) to SDR bt709 so HEVC output plays correctly on SDR clients. */
    public const HDR_FILTER = 'zscale=t=linear:npl=100,format=gbrpf32le,zscale=p=bt709,tonemap=tonemap=hable:desat=0,zscale=t=bt709:m=bt709:r=tv,format=yuv420p';

    protected function buildProcess(): Process
    {
        $replaceOriginal = $this->execution->worker?->replace_original ? 'true' : 'false';

        return new Process([
            $this->script('transcode_media.sh'),
            $this->execution->file_path,
            $replaceOriginal,
            HardwareCapabilities::configuredMode(),
            $this->videoFilter(),
            (string) config('services.ffmpeg.max_bitrate', 0),
        ], base_path());
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
