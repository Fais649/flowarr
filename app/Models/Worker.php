<?php

namespace App\Models;

use App\LibraryJobId;
use App\Observers\WorkerObserver;
use Database\Factories\WorkerFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $id
 * @property string $name
 * @property LibraryJobId|null $job_type
 * @property int $concurrency
 * @property bool $replace_original
 * @property bool $enabled
 */
#[ObservedBy([WorkerObserver::class])]
class Worker extends Model
{
    /**
     * Upper bound of queue worker processes per job type. Matches numprocs of
     * the supervisord programs in docker/prod/supervisord.conf.
     */
    public const MAX_CONCURRENCY = 10;

    /** @use HasFactory<WorkerFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'job_type',
        'concurrency',
        'replace_original',
        'enabled',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'job_type' => LibraryJobId::class,
            'concurrency' => 'integer',
            'replace_original' => 'boolean',
            'enabled' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<Library, $this, Pivot, 'pivot'>
     */
    public function libraries(): BelongsToMany
    {
        return $this->belongsToMany(Library::class, 'library_worker')
            ->withTimestamps();
    }

    /**
     * @return HasMany<Execution, $this>
     */
    public function executions(): HasMany
    {
        return $this->hasMany(Execution::class);
    }
}
