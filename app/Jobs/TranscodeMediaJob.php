<?php

namespace App\Jobs;

use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class TranscodeMediaJob implements DispatchableJob
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public string $filePath) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $inputPath = $this->filePath;
        $outputPath = preg_replace('/(\.[^.]+)$/', 'HEVC$1', $inputPath);
        $command = [
            'ffmpeg',
            '-y',
            '-i', $inputPath,
            '-vf', 'zscale=t=linear:npl=100,format=gbrpf32le,zscale=p=bt709,tonemap=tonemap=hable:desat=0,zscale=t=bt709:m=bt709:r=tv,format=yuv420p',
            '-c:v', 'hevc_nvenc',
            '-preset', 'p4',
            '-c:a', 'copy',
            $outputPath,
        ];

        $process = new Process($command);
        $process->setTimeout(3600);
        $process->run(function ($type, $buffer) {
            Log::info(sprintf('[FFMPEG] %s %s', $type, $buffer));
        });

        if (! $process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }
        Log::info("Transcode successful {$outputPath}");
    }
}
