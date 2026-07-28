<?php

namespace App\Models;

use App\LibraryJobId;
use Database\Factories\WorkerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Worker extends Model
{
    /** @use HasFactory<WorkerFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'job_type',
        'concurrency',
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
        ];
    }
}
