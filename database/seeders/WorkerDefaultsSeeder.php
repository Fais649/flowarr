<?php

namespace Database\Seeders;

use App\LibraryJobId;
use App\Models\Worker;
use Illuminate\Database\Seeder;

class WorkerDefaultsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jobTypes = [
            LibraryJobId::TRANSCODE_MEDIA->value => 'Transcode Worker',
            LibraryJobId::EXTRACT_SUBTITLES->value => 'Subtitle Extraction Worker',
            LibraryJobId::CONVERT_SUBTITLE->value => 'Subtitle Conversion Worker',
        ];

        foreach ($jobTypes as $jobType => $defaultName) {
            Worker::firstOrCreate(
                ['job_type' => $jobType],
                [
                    'name' => $defaultName,
                    'concurrency' => 1,
                    'replace_original' => false,
                    'enabled' => true,
                ],
            );
        }
    }
}
