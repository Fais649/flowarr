<?php

namespace App\Services;

class MediaProbeResult
{
    public const HDR_TRANSFERS = ['smpte2084', 'arib-std-b67', 'smpte428']; // PQ, HLG, ST.428

    /**
     * Create a new class instance.
     *
     * @param  list<string>  $subtitleCodecs
     */
    public function __construct(
        private readonly string $fileExtension,
        private readonly ?string $videoCodec,
        private readonly bool $hasEmbeddedSubs,
        private readonly ?string $colorTransfer = null,
        private readonly ?int $bitsPerRawSample = null,
        private readonly array $subtitleCodecs = [],
        private readonly ?float $duration = null,
        private readonly ?string $colorPrimaries = null,
    ) {}

    public function fileExtension(): string
    {
        return $this->fileExtension;
    }

    public function videoCodec(): ?string
    {
        return $this->videoCodec;
    }

    public function hasEmbeddedSubs(): bool
    {
        return $this->hasEmbeddedSubs;
    }

    /**
     * Whether the file carries subtitle streams that can be extracted to SRT.
     * Image-based formats (PGS, VobSub) cannot be converted to text.
     */
    public function hasTextSubtitles(): bool
    {
        return array_intersect($this->subtitleCodecs, MediaProbeService::TEXT_SUBTITLE_CODECS) !== [];
    }

    /**
     * @return list<string>
     */
    public function subtitleCodecs(): array
    {
        return $this->subtitleCodecs;
    }

    public function duration(): ?float
    {
        return $this->duration;
    }

    public function isVideo(): bool
    {
        return $this->videoCodec !== null
            && ! in_array(strtolower($this->fileExtension), MediaProbeService::SUBTITLE_EXTENSIONS, true);
    }

    public function isSubtitle(): bool
    {
        return in_array(strtolower($this->fileExtension), MediaProbeService::SUBTITLE_EXTENSIONS, true);
    }

    public function isTargetSubtitleExtension(): bool
    {
        return strtolower($this->fileExtension) === MediaProbeService::TARGET_SUBTITLE_EXTENSION;
    }

    public function isTargetVideoEncoding(): bool
    {
        return strtolower($this->videoCodec ?? '') === MediaProbeService::TARGET_ENCODING;
    }

    public function isHdr(): bool
    {
        if ($this->colorTransfer !== null && in_array($this->colorTransfer, self::HDR_TRANSFERS, true)) {
            return true;
        }

        // Fallback when the transfer is missing: 10+ bit video with bt2020 primaries.
        // 10-bit alone is not enough, plenty of SDR encodes (e.g. Hi10P) are 10-bit.
        return $this->colorTransfer === null
            && $this->colorPrimaries === 'bt2020'
            && ($this->bitsPerRawSample ?? 0) >= 10;
    }
}
