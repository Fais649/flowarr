<?php

namespace App\Jobs;

use App\Jobs\Concerns\TracksExecution;
use App\Jobs\Contracts\DispatchableJob;
use App\Services\MediaProbeService;
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

    private const HDR_FILTER = 'zscale=t=linear:npl=100,format=gbrpf32le,zscale=p=bt709,tonemap=tonemap=hable:desat=0,zscale=t=bt709:m=bt709:r=tv,format=yuv420p';

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
            '-vf', $this->resolveVideoFilter($inputPath),
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

            $errorOutput = $process->getErrorOutput();
            // Strip ffmpeg version/configuration banner, keep only actual error lines
            $lines = explode("\n", $errorOutput);
            $errorLines = array_filter($lines, fn ($line) => preg_match('/\[error\]|Error |Unknown |Unrecognized|No such/i', $line));
            $message = ! empty($errorLines)
                ? implode("\n", $errorLines)
                : ($lines[count($lines) - 2] ?? $errorOutput);

            Log::error("Transcode failed for {$this->filePath}: {$message}");

            throw new \RuntimeException($message);
        }

        $this->markExecutionAsCompleted();
        Log::info("Transcode successful {$outputPath}");
    }

    private function resolveVideoFilter(string $filePath): string
    {
        // Allow env override for full control
        $envFilter = config('services.ffmpeg.video_filter');
        if ($envFilter !== null && $envFilter !== '') {
            return $envFilter;
        }

        // Auto-detect HDR — probe the file
        try {
            $probe = app(MediaProbeService::class)->probe($filePath);
            if ($probe->isHdr()) {
                Log::info("HDR detected for {$filePath}, applying tonemap filter");

                return self::HDR_FILTER;
            }
        } catch (\Throwable $e) {
            Log::warning("Probe failed for HDR detection on {$filePath}: {$e->getMessage()}");
        }

        return 'format=yuv420p';
    }

    /** @phpstan-impure */
    protected function shouldPause(): bool
    {
        return Cache::get('media_processing_paused') || Cache::get('active_streams', 0) > 0;
    }
}
