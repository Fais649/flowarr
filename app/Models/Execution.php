<?php

namespace App\Models;

use App\ExecutionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    /**
     * @return BelongsTo<Worker,$this>
     */
    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }
}
