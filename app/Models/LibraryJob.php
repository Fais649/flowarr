<?php

namespace App\Models;

use App\LibraryJobId;
use Database\Factories\LibraryJobFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $library_id
 * @property LibraryJobId $job_id
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Execution> $executions
 * @property-read int|null $executions_count
 * @property-read \App\Models\Library|null $library
 * @method static \Database\Factories\LibraryJobFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LibraryJob newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LibraryJob newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LibraryJob query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LibraryJob whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LibraryJob whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LibraryJob whereJobId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LibraryJob whereLibraryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LibraryJob whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class LibraryJob extends Model
{
    /** @use HasFactory<LibraryJobFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'library_id',
        'job_id',
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
            'library_id' => 'integer',
            'job_id' => LibraryJobId::class,
        ];
    }

    /**
     * @return BelongsTo<Library,$this>
     */
    public function library(): BelongsTo
    {
        return $this->belongsTo(Library::class);
    }

    /**
     * @return HasMany<Execution, $this>
     */
    public function executions(): HasMany
    {
        return $this->hasMany(Execution::class);
    }
}
