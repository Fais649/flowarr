<?php

namespace App;

use App\Jobs\Contracts\DispatchableJob;
use App\Jobs\ConvertSubtitleJob;
use App\Jobs\ExtractSubtitlesJob;
use App\Jobs\TranscodeMediaJob;
use Illuminate\Contracts\Queue\ShouldQueue;

enum LibraryJobId: string
{
    case TRANSCODE_MEDIA = 'transcode_media';
    case EXTRACT_SUBTITLES = 'extract_subs';
    case CONVERT_SUBTITLE = 'convert_sub';

    /**
     * @return class-string<ShouldQueue&DispatchableJob>
     */
    public function getJobClass(): string
    {
        return match ($this) {
            self::TRANSCODE_MEDIA => TranscodeMediaJob::class,
            self::EXTRACT_SUBTITLES => ExtractSubtitlesJob::class,
            self::CONVERT_SUBTITLE => ConvertSubtitleJob::class,
        };
    }

    public function getQueue(): string
    {
        return match ($this) {
            self::TRANSCODE_MEDIA => config('queue.queues.transcode', 'transcode'),
            self::EXTRACT_SUBTITLES, self::CONVERT_SUBTITLE => config('queue.queues.subtitle', 'subtitle'),
        };
    }
}
