<?php

namespace App\Services;

/**
 * Naming rules for transcoded files, shared with scripts/transcode_media.sh.
 */
class TranscodeOutput
{
    public const SUFFIX = '_hevc';

    /**
     * Transcodes are always written as Matroska. With replace_original the
     * result takes the original's name (with an .mkv extension); otherwise it
     * is written next to it with a "_hevc" suffix.
     */
    public static function pathFor(string $filePath, bool $replaceOriginal): string
    {
        $withoutExtension = preg_replace('/\.[^.\/]+$/', '', $filePath);

        return $replaceOriginal
            ? $withoutExtension.'.mkv'
            : $withoutExtension.self::SUFFIX.'.mkv';
    }
}
