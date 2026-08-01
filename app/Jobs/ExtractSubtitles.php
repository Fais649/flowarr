<?php

namespace App\Jobs;

use App\Jobs\Concerns\TracksExecution;
use App\Jobs\Contracts\DispatchableJob;
use App\Jobs\Data\ExtractSubtitlesProcessParam;
use App\MediaJobQueue;
use Closure;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

#[Queue(queue: MediaJobQueue::EXTRACT_SUBTITLES)]
class ExtractSubtitles implements DispatchableJob, ShouldQueue
{
    use Queueable;
    use TracksExecution;

    private const TEXT_BASED_CODECS = [
        'subrip',
        'srt',
        'ass',
        'ssa',
        'webvtt',
    ];

    public function __construct(
        public string $filePath,
        public bool $replaceOriginal = false,
        protected ?Closure $processFactory = null,
        ?int $executionId = null,
        public ?ExtractSubtitlesProcessParam $params = null,
    ) {
        $this->setExecutionId($executionId);

        $this->params = $params ?? new ExtractSubtitlesProcessParam(
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
            Log::info(sprintf('Finished processing subtitles for %s', $this->filePath));
        }
    }

    protected function shouldPause(): bool
    {
        return Cache::get('media_processing_paused') || Cache::get('active_streams', 0) > 0;
    }

    private function executeProcess(): bool
    {
        $success = false;
        try {
            $streams = $this->getSubtitleStreams();

            foreach ($streams as $stream) {
                $this->processStream($stream);
            }

            if ($this->replaceOriginal && ! empty($streams)) {
                $this->replaceOriginal();
            }

            $success = true;
        } catch (Exception $e) {
            $this->markExecutionAsFailed();
            Log::error(sprintf('ExtractSubtitlesJob Exception encountered, Skipping %s: %s', $this->filePath, $e->getMessage()));
        } finally {
            if (file_exists($this->params->targetFilename)) {
                unlink($this->params->targetFilename);
            }
        }

        return $success;
    }

    protected function getSubtitleStreams(): array
    {
        $probeCommand = [
            'ffprobe',
            '-v', 'error',
            '-select_streams', 's',
            '-show_entries', 'stream=index,codec_name:stream_tags=language',
            '-of', 'json',
            $this->filePath,
        ];

        $factory = $this->params->processFactory;
        $probe = $factory($probeCommand);
        $probe->run();

        if (! $probe->isSuccessful()) {
            throw new Exception(sprintf('ffprobe command failed: %s', $probe->getErrorOutput()));
        }

        $outputData = json_decode($probe->getOutput(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception(sprintf('Failed parsing json out from ffprobe: %s', json_last_error_msg()));
        }

        Log::info(sprintf('Discovered streams raw output: %s', $probe->getOutput()));

        return $outputData['streams'] ?? [];
    }

    protected function buildExtractionProcess(
        string $index,
        string $outputPath
    ): Process {
        $extractCommand = [
            'ffmpeg', '-y', '-v', 'error',
            '-i', $this->filePath,
            '-map', sprintf('0:%s', $index),
            $outputPath,
        ];

        $factory = $this->params->processFactory;
        $extract = $factory($extractCommand);
        $extract->setTimeout(null);

        return $extract;
    }

    protected function awaitProcessCompleted(Process $extract, $index, $outputPath)
    {
        while ($extract->isRunning()) {
            if ($this->shouldPause()) {
                posix_kill($extract->getPid(), SIGSTOP);
                while ($this->shouldPause()) {
                    sleep(2);
                }
                posix_kill($extract->getPid(), SIGCONT);
            }
            $extract->checkTimeout();
            usleep(200000);
        }

        if ($extract->isSuccessful()) {
            Log::info(sprintf('Successfully extracted sidecar track %s to %s', $index, $outputPath));
        } else {
            Log::error(sprintf('Failed extracting track %s: %s', $index, $extract->getErrorOutput()));
        }

        return $extract;
    }

    protected function getOutputPath($stream): string
    {
        $languageCode = $stream['tags']['language'] ?? 'und';
        $isoMap = config('languages');
        $shortLang = $isoMap[$languageCode] ?? $languageCode;

        return sprintf('%s/%s.%s.srt', $this->params->basedir, $this->params->filename, $shortLang);
    }

    protected function processStream($stream)
    {
        $codec = $stream['codec_name'] ?? 'unknown';
        $index = $stream['index'] ?? null;

        Log::info(sprintf('Found internal subtitle stream - Codec: %s, Index: %s', $codec, $index));

        if ($index === null) {
            Log::warning('Skipping subtitle stream due to missing index.', ['stream' => $stream]);

            return;
        }

        if (! in_array($codec, self::TEXT_BASED_CODECS)) {
            Log::warning(sprintf('Skipping stream extraction: Codec %s is not text-based.', $codec));

            return;
        }

        $outputPath = $this->getOutputPath($stream);
        $extract = $this->buildExtractionProcess($index, $outputPath);
        $extract->start();

        $this->awaitProcessCompleted($extract, $index, $outputPath);
    }

    protected function replaceOriginal()
    {
        $stripCommand = [
            'mkvmerge',
            '-o', $this->params->targetFilename,
            '-S',
            $this->filePath,
        ];

        $factory = $this->params->processFactory;
        $stripProcess = $factory($stripCommand);
        $stripProcess->setTimeout(null);
        $stripProcess->start();

        while ($stripProcess->isRunning()) {
            if ($this->shouldPause()) {
                posix_kill($stripProcess->getPid(), SIGSTOP);
                while ($this->shouldPause()) {
                    sleep(2);
                }
                posix_kill($stripProcess->getPid(), SIGCONT);
            }
            $stripProcess->checkTimeout();
            usleep(200000);
        }

        if ($stripProcess->isSuccessful()) {
            if (file_exists($this->params->targetFilename) && filesize($this->params->targetFilename) > 0) {
                unlink($this->filePath);
                rename($this->params->targetFilename, $this->filePath);
                Log::info(sprintf('Successfully stripped internal subtitle tracks from %s', $this->filePath));
            } else {
                throw new Exception('mkvmerge completed successfully but output file is missing or empty.');
            }
        } else {
            throw new Exception(sprintf('Failed stripping subtitle tracks via mkvmerge: %s', $stripProcess->getErrorOutput()));
        }
    }
}
