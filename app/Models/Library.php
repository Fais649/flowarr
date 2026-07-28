<?php

namespace App\Models;

use App\LibraryStatus;
use Database\Factories\LibraryFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
            ->has('libraryJobs');
    }
}
