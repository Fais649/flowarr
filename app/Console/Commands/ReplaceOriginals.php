<?php

namespace App\Console\Commands;

use App\ExecutionStatus;
use App\Models\Execution;
use App\Services\OriginalReplacement;
use Illuminate\Console\Command;

class ReplaceOriginals extends Command
{
    protected $signature = 'originals:replace';

    protected $description = 'Commit validated transcodes when their library replacement policy allows it';

    public function handle(OriginalReplacement $replacement): int
    {
        Execution::where('replacement_status', 'pending')->where('status', ExecutionStatus::COMPLETED)
            ->eachById(fn (Execution $execution) => $replacement->replace($execution));

        return self::SUCCESS;
    }
}
