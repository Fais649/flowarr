<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $job_type
 * @property int $max_concurrent
 */
class JobWorkerLimit extends Model
{
    /** @use HasFactory<JobWorkerLimit> */
    use HasFactory;

    protected $fillable = [
        'job_type', 'max_concurrent',
    ];
}
