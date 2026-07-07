<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\PendingDispatch;

interface DispatchableJob extends ShouldQueue
{
    public static function dispatch(mixed ...$args): PendingDispatch;
}
