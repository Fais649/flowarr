<?php

namespace App\Jobs\Data;

use Closure;

class ConvertSubtitleProcessParam extends MediaProcessParam
{
    public function __construct(string $filePath, ?Closure $processFactory = null)
    {
        $basedir = pathinfo($filePath, PATHINFO_DIRNAME);
        $filename = pathinfo($filePath, PATHINFO_FILENAME);
        $targetFilename = sprintf('%s/%s.srt', $basedir, $filename);
        parent::__construct($filePath, $processFactory, $targetFilename);
    }
}
