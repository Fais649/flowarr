<?php

namespace App\Models;

use App\ExecutionStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $library_job_id
 * @property string|null $worker_id
 * @property string $file_path
 * @property ExecutionStatus $status
 * @property int|null $started_at
 * @property int|null $finished_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read LibraryJob $libraryJob
 *
 * @method static \Database\Factories\ExecutionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Execution newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Execution newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Execution query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Execution whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Execution whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Execution whereFinishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Execution whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Execution whereLibraryJobId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Execution whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Execution whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Execution whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Execution whereWorkerId($value)
 *
 * @mixin \Eloquent
 */
class Execution extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'library_job_id',
        'worker_id',
        'file_path',
        'status',
        'started_at',
        'finished_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'library_job_id' => 'integer',
            'status' => ExecutionStatus::class,
            'started_at' => 'timestamp',
            'finished_at' => 'timestamp',
        ];
    }

    public function libraryJob(): BelongsTo
    {
        return $this->belongsTo(LibraryJob::class);
    }
}
