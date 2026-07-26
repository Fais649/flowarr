<?php

namespace App\Jobs;

use App\Jobs\Concerns\TracksExecution;
use App\Jobs\Contracts\DispatchableJob;
use Closure;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class ExtractSubtitlesJob implements DispatchableJob, ShouldQueue
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
        protected ?Closure $processFactory = null,
        ?int $executionId = null,
    ) {
        $this->onQueue(config('queue.queues.subtitle', 'subtitle'));
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
        $outputFile = sprintf('%s.tmp.mkv', $this->filePath);

        $factory = $this->processFactory ?? fn (array $command) => new Process($command);

        try {
            $probeCommand = [
                'ffprobe',
                '-v', 'error',
                '-select_streams', 's',
                '-show_entries', 'stream=index,codec_name:stream_tags=language',
                '-of', 'json',
                $this->filePath,
            ];

            $probe = $factory($probeCommand);
            $probe->run();

            if (! $probe->isSuccessful()) {
                throw new Exception(sprintf('ffprobe command failed: %s', $probe->getErrorOutput()));
            }

            $outputData = json_decode($probe->getOutput(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception(sprintf('Failed parsing json out from ffprobe: %s', json_last_error_msg()));
            }

            $streams = $outputData['streams'] ?? [];
            Log::info(sprintf('Discovered streams raw output: %s', $probe->getOutput()));

            foreach ($streams as $stream) {
                $codec = $stream['codec_name'] ?? 'unknown';
                $index = $stream['index'] ?? null;

                Log::info(sprintf('Found internal subtitle stream - Codec: %s, Index: %s', $codec, $index));

                if ($index === null) {
                    Log::warning('Skipping subtitle stream due to missing index.', ['stream' => $stream]);

                    continue;
                }

                if (! in_array($codec, self::TEXT_BASED_CODECS)) {
                    Log::warning(sprintf('Skipping stream extraction: Codec %s is not text-based.', $codec));

                    continue;
                }

                $languageCode = $stream['tags']['language'] ?? 'und';
                $isoMap = config('languages');
                $shortLang = $isoMap[$languageCode] ?? $languageCode;

                $outputPath = sprintf('%s/%s.%s.srt', $dir, $baseName, $shortLang);

                $extractCommand = [
                    'ffmpeg', '-y', '-v', 'error',
                    '-i', $this->filePath,
                    '-map', sprintf('0:%s', $index),
                    $outputPath,
                ];

                $extract = $factory($extractCommand);
                $extract->setTimeout(null);
                $extract->start();
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
            }

            if (! empty($streams)) {
                $stripCommand = [
                    'mkvmerge',
                    '-o', $outputFile,
                    '-S',
                    $this->filePath,
                ];

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
                    if (file_exists($outputFile) && filesize($outputFile) > 0) {
                        unlink($this->filePath);
                        rename($outputFile, $this->filePath);
                        Log::info(sprintf('Successfully stripped internal subtitle tracks from %s', $this->filePath));
                    } else {
                        throw new Exception('mkvmerge completed successfully but output file is missing or empty.');
                    }
                } else {
                    throw new Exception(sprintf('Failed stripping subtitle tracks via mkvmerge: %s', $stripProcess->getErrorOutput()));
                }
            }

        } catch (Exception $e) {
            $this->markExecutionAsFailed();
            Log::error(sprintf('ExtractSubtitlesJob Exception encountered: %s', $e->getMessage()));
        } finally {
            if (isset($outputFile) && file_exists($outputFile)) {
                unlink($outputFile);
            }
        }

        $this->markExecutionAsCompleted();
        Log::info(sprintf('Finished processing subtitles for %s', $this->filePath));
    }

    /** @phpstan-impure */
    protected function shouldPause(): bool
    {
        return Cache::get('media_processing_paused') || Cache::get('active_streams', 0) > 0;
    }
}
