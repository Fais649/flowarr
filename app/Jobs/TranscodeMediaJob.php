<?php

namespace App\Jobs;

use App\LibraryJobId;
use App\Traits\HasJobSlot;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class TranscodeMediaJob implements ShouldQueue
{
    use HasJobSlot, Queueable;

    public static function jobType(): string
    {
        return LibraryJobId::TRANSCODE_MEDIA->value;
    }

    /**
     * Create a new job instance.
     */
    public function __construct(public string $filePath, protected ?Process $process = null) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $lock = $this->acquireSlot(3600);
        if (! $lock) {
            $this->release(5);

            return;
        }

        try {
            $inputPath = $this->filePath;
            $outputPath = preg_replace('/(\.[^.]+)$/', 'HEVC$1', $inputPath);
            $useNvenc = config('services.ffmpeg.use_nvenc', true);

            $videoCodec = $useNvenc ? 'hevc_nvenc' : 'libx265';

            $preset = match ($videoCodec) {
                'hevc_nvenc' => 'p4',
                default => 'medium',
            };

            $command = [
                config('services.ffmpeg.bin', 'ffmpeg'),
                '-y',
                '-i', $inputPath,
                '-vf', config('services.ffmpeg.video_filter', 'zscale=t=linear:npl=100,format=gbrpf32le,zscale=p=bt709,tonemap=tonemap=hable:desat=0,zscale=t=bt709:m=bt709:r=tv,format=yuv420p'),
                '-c:v', $videoCodec,
                '-preset', $preset,
                '-c:a', 'copy',
                $outputPath,
            ];

            $process = $this->process ?? new Process($command);
            $process->setTimeout(null);
            if (! $process->isStarted()) {
                $process->setTimeout(null);
                $process->start();
            }

            while ($process->isRunning()) {
                if (Cache::get('media_processing_paused')) {
                    posix_kill($process->getPid(), SIGSTOP);

                    while (Cache::get('media_processing_paused')) {
                        sleep(2);
                    }

                    posix_kill($process->getPid(), SIGCONT);
                }
                $process->checkTimeout();
                usleep(200000);
            }

            if (! $process->isSuccessful()) {
                throw new \RuntimeException($process->getErrorOutput());
            }
            Log::info("Transcode successful {$outputPath}");
        } finally {
            $lock->release();
        }
    }
}
