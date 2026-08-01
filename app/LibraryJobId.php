<?php

namespace App;

use App\Jobs\ConvertSubtitle;
use App\Jobs\ExecutionJob;
use App\Jobs\ExtractSubtitles;
use App\Jobs\TranscodeMedia;
use App\Models\Execution;

enum LibraryJobId: string
{
    case TRANSCODE_MEDIA = 'transcode_media';
    case EXTRACT_SUBTITLES = 'extract_subs';
    case CONVERT_SUBTITLE = 'convert_sub';

    /**
     * @return class-string<ExecutionJob>
     */
    public function getJobClass(): string
    {
        return match ($this) {
            self::TRANSCODE_MEDIA => TranscodeMedia::class,
            self::EXTRACT_SUBTITLES => ExtractSubtitles::class,
            self::CONVERT_SUBTITLE => ConvertSubtitle::class,
        };
    }

    public function dispatch(Execution $execution)
    {
        match ($this) {
            self::TRANSCODE_MEDIA => TranscodeMedia::dispatch($execution),
            self::EXTRACT_SUBTITLES => ExtractSubtitles::dispatch($execution),
            self::CONVERT_SUBTITLE => ConvertSubtitle::dispatch($execution),
        };
    }
}
