<?php

namespace App\Jobs;

use App\Jobs\Concerns\TracksExecution;
use App\Jobs\Contracts\DispatchableJob;
use App\MediaJobQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

#[Queue(queue: MediaJobQueue::CONVERT_SUBTITLE)]
class ConvertSubtitleJob implements DispatchableJob, ShouldQueue
{
    use Queueable;
    use TracksExecution;

    public function __construct(
        private string $filePath,
        public bool $replaceOriginal = false,
        ?int $executionId = null,
    ) {
        $this->setExecutionId($executionId);
    }

    public function handle(): void
    {
        $this->markExecutionAsProcessing();

        if (! file_exists($this->filePath)) {
            Log::error(sprintf('Target file does not exist or is inaccessible: %s', $this->filePath));

            return;
        }

        $dir = pathinfo($this->filePath, PATHINFO_DIRNAME);
        $baseName = pathinfo($this->filePath, PATHINFO_FILENAME);
        $extension = strtolower(pathinfo($this->filePath, PATHINFO_EXTENSION));
        if ($extension === 'srt') {
            return;
        }

        $outputFile = sprintf('%s/%s.srt', $dir, $baseName);

        try {
            $convertCommand = [
                config('services.ffmpeg.bin', 'ffmpeg'),
                '-i',
                $this->filePath,
                '-c:s',
                'srt',
                $outputFile,
            ];

            $convert = new Process($convertCommand);
            $convert->setTimeout(null);
            $convert->start();

            while ($convert->isRunning()) {
                if ($this->shouldPause()) {
                    posix_kill($convert->getPid(), SIGSTOP);

                    while ($convert->isRunning() && $this->shouldPause()) {
                        sleep(2);
                    }

                    posix_kill($convert->getPid(), SIGCONT);
                }
                $convert->checkTimeout();
                usleep(200000);
            }

            if (! $convert->isSuccessful()) {
                throw new Exception(sprintf('ffmpeg command failed: %s', $convert->getErrorOutput()));
            }

            if ($this->replaceOriginal) {
                unlink($this->filePath);
            }
        } catch (Exception $e) {
            $this->markExecutionAsFailed();
            Log::error(sprintf('ConvertSubtitleJob Exception encountered: %s', $e->getMessage()));
            throw $e;
        }

        $this->markExecutionAsCompleted();
        Log::info(sprintf('Finished converting subtitles for %s', $this->filePath));
    }

    /** @phpstan-impure */
    protected function shouldPause(): bool
    {
        return Cache::get('media_processing_paused') || Cache::get('active_streams', 0) > 0;
    }
}
