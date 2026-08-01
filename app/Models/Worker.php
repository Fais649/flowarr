<?php

namespace App\Models;

use App\LibraryJobId;
use App\Observers\WorkerObserver;
use Database\Factories\WorkerFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $id
 * @property string $name
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property LibraryJobId|null $job_type
 * @property int $concurrency
 * @property bool $replace_original
 * @property bool $enabled
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Library> $libraries
 * @property-read int|null $libraries_count
 * @method static \Database\Factories\WorkerFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Worker newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Worker newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Worker query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Worker whereConcurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Worker whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Worker whereEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Worker whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Worker whereJobType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Worker whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Worker whereReplaceOriginal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Worker whereUpdatedAt($value)
 * @mixin \Eloquent
 */
#[ObservedBy([WorkerObserver::class])]
class Worker extends Model
{
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
}
