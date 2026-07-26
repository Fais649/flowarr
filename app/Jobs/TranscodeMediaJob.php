<?php

namespace App\Jobs;

use App\Jobs\Concerns\TracksExecution;
use App\Jobs\Contracts\DispatchableJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class TranscodeMediaJob implements DispatchableJob, ShouldQueue
{
    use Queueable;
    use TracksExecution;

    public function __construct(
        public string $filePath,
        protected ?Process $process = null,
        ?int $executionId = null,
    ) {
        $this->onQueue(config('queue.queues.transcode', 'transcode'));
        $this->setExecutionId($executionId);
    }

    public function handle(): void
    {
        $this->markExecutionAsProcessing();

        $inputPath = $this->filePath;
        $outputPath = preg_replace('/(\.[^.]+)$/', 'HEVC$1', $inputPath);

        // Check if we should use software encoding for compatibility (e.g. in CI or if NVENC is unavailable)
        // We can use a config value to toggle this.
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
            if ($this->shouldPause()) {
                posix_kill($process->getPid(), SIGSTOP);

                while ($this->shouldPause()) {
                    sleep(2);
                }

                posix_kill($process->getPid(), SIGCONT);
            }
            $process->checkTimeout();
            usleep(200000);
        }

        if (! $process->isSuccessful()) {
            $this->markExecutionAsFailed();

            throw new \RuntimeException($process->getErrorOutput());
        }

        $this->markExecutionAsCompleted();
        Log::info("Transcode successful {$outputPath}");
    }

    /** @phpstan-impure */
    protected function shouldPause(): bool
    {
        return Cache::get('media_processing_paused') || Cache::get('active_streams', 0) > 0;
    }
}
