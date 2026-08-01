<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobWorkerLimit extends Model
{
    /** @use HasFactory<JobWorkerLimit> */
    use HasFactory;

    protected $fillable = [
        'job_type', 'max_concurrent',
    ];
}
