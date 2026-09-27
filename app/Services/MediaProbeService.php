<?php

namespace App\Services;

use FFMpeg\FFProbe;
use Throwable;

class MediaProbeService
{
    public const VIDEO_EXTENSIONS = ['mkv', 'mp4', 'avi', 'mov', 'm4v', 'wmv', 'mts', 'm2ts', 'ts', 'flv', 'webm', 'mpg', 'mpeg'];

    /** Text-based subtitle files that ffmpeg can convert to SRT. */
    public const SUBTITLE_EXTENSIONS = ['ass', 'ssa', 'vtt'];

    /** Embedded subtitle codecs that can be extracted to SRT sidecars. */
    public const TEXT_SUBTITLE_CODECS = ['subrip', 'srt', 'ass', 'ssa', 'webvtt', 'mov_text', 'text'];

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
        $firstVideo = $streams->videos()->first();

        $subtitleCodecs = collect($streams->all())
            ->filter(fn ($s) => $s->get('codec_type') === 'subtitle')
            ->map(fn ($s) => (string) $s->get('codec_name'))
            ->values()
            ->all();

        $bitsPerRawSample = $firstVideo?->get('bits_per_raw_sample');

        return new MediaProbeResult(
            fileExtension: $extension,
            videoCodec: $firstVideo?->get('codec_name'),
            hasEmbeddedSubs: $subtitleCodecs !== [],
            colorTransfer: $firstVideo?->get('color_transfer'),
            bitsPerRawSample: $bitsPerRawSample !== null ? (int) $bitsPerRawSample : null,
            subtitleCodecs: $subtitleCodecs,
            duration: $this->duration($filePath),
            colorPrimaries: $firstVideo?->get('color_primaries'),
        );
    }

    private function duration(string $filePath): ?float
    {
        try {
            $duration = $this->ffprobe->format($filePath)->get('duration');

            return $duration !== null ? (float) $duration : null;
        } catch (Throwable) {
            return null;
        }
    }
}
