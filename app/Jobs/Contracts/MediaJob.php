<?php

namespace App\Jobs\Contracts;

use App\MediaJobQueue;

interface MediaJob extends DispatchableJob
{
    public MediaJobQueue $queue { get; set; }

    public function handle(): void;
}
