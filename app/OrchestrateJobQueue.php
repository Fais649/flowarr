<?php

namespace App;

enum OrchestrateJobQueue: string
{
    case ORCHESTRATE_WORKERS = 'orchestrate-workers';
    case SCAN_LIBRARIES = 'scan-libraries';
}
