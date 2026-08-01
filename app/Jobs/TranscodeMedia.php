<?php

namespace App\Jobs;

use App\Jobs\Concerns\TracksExecution;
use App\Jobs\Contracts\DispatchableJob;
use App\Jobs\Data\TranscodeMediaProcessParam;
use App\MediaJobQueue;
use App\Services\MediaProbeService;
use Closure;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

#[Queue(queue: MediaJobQueue::TRANSCODE_MEDIA)]
class TranscodeMedia implements DispatchableJob, ShouldQueue
{
    use Queueable;
    use TracksExecution;

    private const HDR_FILTER = 'zscale=t=linear:npl=100,format=gbrpf32le,zscale=p=bt709,tonemap=tonemap=hable:desat=0,zscale=t=bt709:m=bt709:r=tv,format=yuv420p';

    public function __construct(
        public string $filePath,
        public bool $replaceOriginal = false,
        protected ?Closure $processFactory = null,
        ?int $executionId = null,
        public ?TranscodeMediaProcessParam $params = null,
    ) {
        $this->setExecutionId($executionId);

        $this->params = $params ?? new TranscodeMediaProcessParam(
            $filePath,
            $processFactory
        );
    }

    public function handle(): void
    {
        $this->markExecutionAsProcessing();

        if (! file_exists($this->filePath)) {
            Log::error(sprintf('Target file does not exist or is inaccessible: %s', $this->filePath));
            $this->markExecutionAsFailed();

            return;
        }

        $success = $this->executeProcess();

        if ($success) {
            $this->markExecutionAsCompleted();
            Log::info(sprintf('Finished processing transcode for %s', $this->filePath));
        }
    }

    protected function shouldPause(): bool
    {
        return Cache::get('media_processing_paused') || Cache::get('active_streams', 0) > 0;
    }

    private function executeProcess(): bool
    {
        $useNvenc = config('services.ffmpeg.use_nvenc', true);
        $videoCodec = $useNvenc ? 'hevc_nvenc' : 'libx265';

        $preset = match ($videoCodec) {
            'hevc_nvenc' => 'p4',
            default => 'medium',
        };

        $rateControlFlags = match ($videoCodec) {
            'hevc_nvenc' => ['-cq', '28'],
            default => ['-crf', '28'],
        };

        $command = array_merge([
            config('services.ffmpeg.bin', 'ffmpeg'),
            '-y',
            '-i', $this->filePath,
            '-vf', $this->resolveVideoFilter($this->filePath),
            '-c:v', $videoCodec,
            '-preset', $preset,
        ], $rateControlFlags, [
            '-c:a', 'copy',
            $this->params->targetFilename,
        ]);

        $factory = $this->params->processFactory;
        $process = $factory($command);
        $process->setTimeout(null);

        if (! $process->isStarted()) {
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
            $lines = explode("\n", $errorOutput);
            $errorLines = array_filter($lines, fn ($line) => preg_match('/\[error\]|Error |Unknown |Unrecognized|No such/i', $line));
            $message = ! empty($errorLines)
                ? implode("\n", $errorLines)
                : ($lines[count($lines) - 2] ?? $errorOutput);

            Log::error("Transcode failed for {$this->filePath}: {$message}");

            throw new \RuntimeException($message);
        }

        if ($this->replaceOriginal) {
            if (file_exists($this->params->targetFilename) && filesize($this->params->targetFilename) > 0) {
                unlink($this->filePath);
                rename($this->params->targetFilename, $this->filePath);
                Log::info("Replaced original file with transcoded version: {$this->filePath}");
            } else {
                throw new \Exception('FFmpeg completed successfully but output file is missing or empty.');
            }
        }

        return true;
    }

    private function resolveVideoFilter(string $filePath): string
    {
        $envFilter = config('services.ffmpeg.video_filter');
        if ($envFilter !== null && $envFilter !== '') {
            return $envFilter;
        }

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
}
