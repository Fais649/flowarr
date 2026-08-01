<?php

namespace App\Models;

use App\LibraryStatus;
use Carbon\CarbonImmutable;
use Database\Factories\LibraryFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $base_path
 * @property LibraryStatus $status
 * @property int $scan_interval
 * @property int|null $last_scan
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Collection<int, LibraryJob> $libraryJobs
 * @property-read int|null $library_jobs_count
 * @property-read Collection<int, Worker> $workers
 * @property-read int|null $workers_count
 *
 * @method static Builder<static>|Library dueForScan()
 * @method static \Database\Factories\LibraryFactory factory($count = null, $state = [])
 * @method static Builder<static>|Library newModelQuery()
 * @method static Builder<static>|Library newQuery()
 * @method static Builder<static>|Library query()
 * @method static Builder<static>|Library whereBasePath($value)
 * @method static Builder<static>|Library whereCreatedAt($value)
 * @method static Builder<static>|Library whereId($value)
 * @method static Builder<static>|Library whereLastScan($value)
 * @method static Builder<static>|Library whereScanInterval($value)
 * @method static Builder<static>|Library whereStatus($value)
 * @method static Builder<static>|Library whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Library extends Model
{
    /** @use HasFactory<LibraryFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'base_path',
        'status',
        'scan_interval',
        'last_scan',
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
            'status' => LibraryStatus::class,
            'last_scan' => 'timestamp',
        ];
    }

    /**
     * @return HasMany<LibraryJob, $this>
     */
    public function libraryJobs(): HasMany
    {
        return $this->hasMany(LibraryJob::class);
    }

    /**
     * @return BelongsToMany<Worker, $this>
     */
    public function workers(): BelongsToMany
    {
        return $this->belongsToMany(Worker::class, 'library_worker')
            ->withTimestamps();
    }

    /**
     * @param  Builder<Library>  $query
     */
    #[Scope]
    protected function dueForScan(Builder $query): void
    {
        $query->with('libraryJobs')
            ->whereIn('status', [LibraryStatus::PENDING, LibraryStatus::PENDING_SCAN])
            ->where(function (Builder $q): void {
                $q->where('status', LibraryStatus::PENDING_SCAN)
                    ->orWhereNull('last_scan')
                    ->orWhereRaw('EXTRACT(EPOCH FROM NOW() - last_scan) >= scan_interval');
            })
            ->where(function (Builder $q): void {
                $q->has('libraryJobs')
                    ->orHas('workers');
            });
    }
}
