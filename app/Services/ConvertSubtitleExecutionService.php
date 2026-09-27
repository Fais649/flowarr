<?php

namespace App\Services;

use Symfony\Component\Process\Process;

class ConvertSubtitleExecutionService extends ProcessExecutionService
{
    protected function buildProcess(): Process
    {
        $replaceOriginal = $this->execution->worker?->replace_original ? 'true' : 'false';

        return new Process([
            $this->script('convert_subtitle.sh'),
            $this->execution->file_path,
            $replaceOriginal,
        ], base_path());
    }
}
