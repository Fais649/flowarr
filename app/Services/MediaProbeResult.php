<?php

namespace App\Services;

class MediaProbeResult
{
    public const HDR_TRANSFERS = ['smpte2084', 'arib-std-b67', 'smpte428']; // PQ, HLG, ST.428

    /**
     * Create a new class instance.
     */
    public function __construct(
        private readonly string $fileExtension,
        private readonly ?string $videoCodec,
        private readonly bool $hasEmbeddedSubs,
        private readonly ?string $colorTransfer = null,
        private readonly ?int $bitsPerRawSample = null,
    ) {}

    public function fileExtension(): ?string
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

    public function isVideo(): bool
    {
        return $this->videoCodec !== null;
    }

    public function isSubtitle(): bool
    {
        return in_array($this->fileExtension, MediaProbeService::SUBTITLE_EXTENSIONS);
    }

    public function isTargetSubtitleExtension(): bool
    {
        return strtolower($this->fileExtension) == MediaProbeService::TARGET_SUBTITLE_EXTENSION;
    }

    public function isTargetVideoEncoding(): bool
    {
        return strtolower($this->videoCodec ?? '') === MediaProbeService::TARGET_ENCODING;
    }

    public function isHdr(): bool
    {
        if ($this->colorTransfer !== null && in_array($this->colorTransfer, self::HDR_TRANSFERS)) {
            return true;
        }

        // Fallback: 10+ bit with bt2020 primaries is almost certainly HDR
        if (($this->bitsPerRawSample ?? 0) >= 10) {
            return true;
        }

        return false;
    }
}
