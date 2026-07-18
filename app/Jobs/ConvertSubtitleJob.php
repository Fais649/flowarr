<?php

namespace App\Jobs;

use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class ConvertSubtitleJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(private string $filePath)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
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
            $convert->run();

            if (! $convert->isSuccessful()) {
                throw new Exception(sprintf('ffmpeg command failed: %s', $convert->getErrorOutput()));
            }
        } catch (Exception $e) {
            Log::error(sprintf('ProcessSubtitleJob Exception encountered: %s', $e->getMessage()));
            throw $e;
        } finally {
            unlink($this->filePath);
        }

        Log::info(sprintf('Finished converting subtitles for %s', $this->filePath));
    }
}
