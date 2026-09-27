<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('scan:libraries')->everyMinute()->withoutOverlapping();
Schedule::command('executions:reap')->everyMinute()->withoutOverlapping();
