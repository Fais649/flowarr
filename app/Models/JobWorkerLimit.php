<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $job_type
 * @property int $max_concurrent
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 *
 * @method static \Database\Factories\JobWorkerLimitFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobWorkerLimit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobWorkerLimit newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobWorkerLimit query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobWorkerLimit whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobWorkerLimit whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobWorkerLimit whereJobType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobWorkerLimit whereMaxConcurrent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobWorkerLimit whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class JobWorkerLimit extends Model
{
    /** @use HasFactory<JobWorkerLimit> */
    use HasFactory;

    protected $fillable = [
        'job_type', 'max_concurrent',
    ];
}
