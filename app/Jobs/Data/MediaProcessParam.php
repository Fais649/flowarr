<?php

namespace App\Jobs\Data;

use Closure;
use Symfony\Component\Process\Process;

abstract class MediaProcessParam
{
    public string $basedir;

    public string $filename;

    public string $targetFilename;

    public Closure $processFactory;

    public function __construct(string $filePath, ?Closure $processFactory, string $targetFilename)
    {
        $this->basedir = pathinfo($filePath, PATHINFO_DIRNAME);
        $this->filename = pathinfo($filePath, PATHINFO_FILENAME);
        $this->targetFilename = $targetFilename;
        $this->processFactory = $processFactory ?? fn (array $command) => new Process($command);
    }
}
