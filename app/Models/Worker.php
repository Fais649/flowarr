<?php

namespace App\Models;

use App\LibraryJobId;
use App\Observers\WorkerObserver;
use Database\Factories\WorkerFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'job_type' => LibraryJobId::class,
            'concurrency' => 'integer',
            'replace_original' => 'boolean',
        ];
    }
}
