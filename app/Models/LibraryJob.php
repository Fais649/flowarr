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
