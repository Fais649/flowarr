<?php

namespace App\Services;

use Symfony\Component\Process\Process;

class TranscodeExecutionService extends ProcessExecutionService
{
    private const HDR_FILTER = 'zscale=t=linear:npl=100,format=gbrpf32le,zscale=p=bt709,tonemap=tonemap=hable:desat=0,zscale=t=bt709:m=bt709:r=tv,format=yuv420p';

    protected function buildProcess(): Process
    {
        $mode = config('services.ffmpeg.enable_gpu_transcoding', true) ? 'auto' : 'software';
        $replaceOriginal = $this->execution->worker?->replace_original ? 'true' : 'false';

        $command = [
            './scripts/transcode_media.sh',
            $this->execution->file_path,
            $replaceOriginal,
            $mode,
        ];

        $process = new Process($command);
        $process->setTimeout(null);

        return $process;
    }
}
