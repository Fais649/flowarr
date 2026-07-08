<?php

namespace App;

enum LibraryStatus: string
{
    case PENDING = 'pending';
    case PENDING_SCAN = 'pending_scan';
    case SCANNING = 'scanning';
    case PAUSED = 'paused';
    case STOPPED = 'stopped';
}
