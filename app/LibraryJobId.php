<?php

namespace App;

use App\Jobs\ConvertSubtitleJob;
use App\Jobs\DispatchableJob;
use App\Jobs\ExtractSubtitlesJob;
use App\Jobs\TranscodeMediaJob;

enum LibraryJobId: string
{
    case TRANSCODE_MEDIA = 'transcode_media';
    case EXTRACT_SUBTITLES = 'extract_subs';
    case CONVERT_SUBTITLE = 'convert_sub';

    /**
     * @return class-string<DispatchableJob>
     */
    public function getJobClass(): string
    {
        return match ($this) {
            self::TRANSCODE_MEDIA => TranscodeMediaJob::class,
            self::EXTRACT_SUBTITLES => ExtractSubtitlesJob::class,
            self::CONVERT_SUBTITLE => ConvertSubtitleJob::class,
        };
    }
}
