<?php

namespace App\Jobs\Data;

use Closure;

class TranscodeMediaProcessParam extends MediaProcessParam
{
    public function __construct(string $filePath, ?Closure $processFactory = null)
    {
        $targetFilename = preg_replace('/(\.[^.]+)$/', '_HEVC$1', $filePath);
        parent::__construct($filePath, $processFactory, $targetFilename);
    }
}
