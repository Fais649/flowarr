<?php

namespace App\Services;

class MediaProbeResult
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        private readonly string $fileExtension,
        private readonly ?string $videoCodec,
        private readonly bool $hasEmbeddedSubs,
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
}
