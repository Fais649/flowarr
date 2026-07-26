<?php

namespace App\Jobs\Contracts;

interface DispatchableJob
{
    public function handle(): void;
}
