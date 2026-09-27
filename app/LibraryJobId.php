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

    public function dispatch(Execution $execution): void
    {
        match ($this) {
            self::TRANSCODE_MEDIA => TranscodeMedia::dispatch($execution),
            self::EXTRACT_SUBTITLES => ExtractSubtitles::dispatch($execution),
            self::CONVERT_SUBTITLE => ConvertSubtitle::dispatch($execution),
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::TRANSCODE_MEDIA => 'Transcode Media',
            self::EXTRACT_SUBTITLES => 'Extract Subtitles',
            self::CONVERT_SUBTITLE => 'Convert Subtitles',
        };
    }

    /**
     * Name of the supervisord program that consumes this job type's queue.
     */
    public function supervisorProgram(): string
    {
        return match ($this) {
            self::TRANSCODE_MEDIA => 'Transcoder',
            self::EXTRACT_SUBTITLES => 'ExtractSubs',
            self::CONVERT_SUBTITLE => 'ConvertSubs',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $id) => ['value' => $id->value, 'label' => $id->label()], self::cases());
    }
}
