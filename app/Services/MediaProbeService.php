<?php

namespace App\Services;

use FFMpeg\FFProbe;

class MediaProbeService
{
    public const VIDEO_EXTENSIONS = ['mkv', 'mp4', 'avi', 'mov', 'm4v', 'wmv', 'ts', 'mts'];

    public const SUBTITLE_EXTENSIONS = ['ass', 'ssa', 'sub', 'idx', 'sup', 'pgs'];

    public const TARGET_ENCODING = 'hevc';

    public const TARGET_SUBTITLE_EXTENSION = 'srt';

    /**
     * Create a new class instance.
     */
    public function __construct(private FFProbe $ffprobe) {}

    public function probe(string $filePath): MediaProbeResult
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $streams = $this->ffprobe->streams($filePath);
        $videos = $streams->videos();
        $firstVideo = $videos->first();

        $videoCodec = $firstVideo?->get('codec_name');
        $hasEmbeddedSubs = collect($streams->all())->contains(
            fn ($s) => $s->get('codec_type') === 'subtitle');

        return new MediaProbeResult($extension, $videoCodec, $hasEmbeddedSubs);
    }
}
