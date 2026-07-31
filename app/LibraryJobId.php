<?php

namespace App;

use App\Jobs\Contracts\DispatchableJob;
use App\Jobs\ConvertSubtitle;
use App\Jobs\ExtractSubtitles;
use App\Jobs\TranscodeMedia;
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
            self::TRANSCODE_MEDIA => TranscodeMedia::class,
            self::EXTRACT_SUBTITLES => ExtractSubtitles::class,
            self::CONVERT_SUBTITLE => ConvertSubtitle::class,
        };
    }
}
