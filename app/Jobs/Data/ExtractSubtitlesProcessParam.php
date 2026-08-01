<?php

namespace App\Jobs\Data;

use Closure;

class ExtractSubtitlesProcessParam extends MediaProcessParam
{
    public function __construct(string $filePath, ?Closure $processFactory = null)
    {
        $targetFilename = sprintf('%s.tmp.mkv', $filePath);
        parent::__construct($filePath, $processFactory, $targetFilename);
    }
}
