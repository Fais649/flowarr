<?php

namespace App\Models;

use App\ExecutionStatus;
use Carbon\CarbonImmutable;
use Database\Factories\ExecutionFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $library_job_id
 * @property int|null $worker_id
 * @property string|null $replacement_status
 * @property array<string, mixed>|null $replacement
 * @property string $file_path
 * @property int|null $file_size
 * @property int|null $file_mtime
 * @property ExecutionStatus $status
 * @property float|null $progress
 * @property string|null $message
 * @property string|null $output
 * @property CarbonImmutable|null $started_at
 * @property CarbonImmutable|null $heartbeat_at
 * @property CarbonImmutable|null $finished_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read LibraryJob|null $libraryJob
 * @property-read Worker|null $worker
 */
class Execution extends Model
{
    /** @use HasFactory<ExecutionFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'library_job_id',
        'worker_id',
        'file_path',
        'replacement_status',
        'replacement',
        'file_size',
        'file_mtime',
        'status',
        'progress',
        'message',
        'output',
        'started_at',
        'heartbeat_at',
        'finished_at',
    ];

    /**
     * The raw process log can be large, so it is only sent to the detail page.
     *
     * @var list<string>
     */
    protected $hidden = [
        'output',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'replacement' => 'array',
            'id' => 'integer',
            'library_job_id' => 'integer',
            'worker_id' => 'integer',
            'file_size' => 'integer',
            'file_mtime' => 'integer',
            'status' => ExecutionStatus::class,
            'progress' => 'float',
            'started_at' => 'datetime',
            'heartbeat_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<LibraryJob, $this>
     */
    public function libraryJob(): BelongsTo
    {
        return $this->belongsTo(LibraryJob::class);
    }

    /**
     * @return BelongsTo<Worker, $this>
     */
    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }

    /**
     * @param  Builder<Execution>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereIn('status', ExecutionStatus::active());
    }

    /**
     * Whether the file on disk still matches the size/mtime recorded when this
     * execution was created. Legacy rows without a fingerprint always match.
     */
    public function matchesFingerprint(?int $size, ?int $mtime): bool
    {
        if ($this->file_size === null || $this->file_mtime === null) {
            return true;
        }

        return $this->file_size === $size && $this->file_mtime === $mtime;
    }

    /**
     * Wall-clock seconds the execution has been (or was) running.
     */
    public function durationSeconds(): ?int
    {
        if ($this->started_at === null) {
            return null;
        }

        $end = $this->finished_at ?? now();

        return (int) max(0, $this->started_at->diffInSeconds($end));
    }
}
