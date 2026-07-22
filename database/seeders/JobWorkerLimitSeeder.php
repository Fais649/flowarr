<?php

namespace Database\Seeders;

use App\LibraryJobId;
use App\Models\JobWorkerLimit;
use Illuminate\Database\Seeder;

class JobWorkerLimitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        JobWorkerLimit::factory()->create([
            ['job_type' => LibraryJobId::TRANSCODE_MEDIA->value, 'max_concurrent' => 1],
            ['job_type' => LibraryJobId::CONVERT_SUBTITLE->value, 'max_concurrent' => 3],
            ['job_type' => LibraryJobId::EXTRACT_SUBTITLES->value, 'max_concurrent' => 2],
        ]);
    }
}
