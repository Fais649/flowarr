<?php

namespace Database\Seeders;

use App\ExecutionStatus;
use App\LibraryJobId;
use App\LibraryStatus;
use App\Models\Execution;
use App\Models\Library;
use App\Models\LibraryJob;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Database\Seeder;

class BrowserTestSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
            ],
        );

        $library = Library::firstOrCreate(
            ['base_path' => '/tmp'],
            [
                'status' => LibraryStatus::PENDING,
                'scan_interval' => 3600,
            ],
        );

        $transcodeJob = LibraryJob::firstOrCreate(
            ['library_id' => $library->id, 'job_id' => LibraryJobId::TRANSCODE_MEDIA],
        );

        LibraryJob::firstOrCreate(
            ['library_id' => $library->id, 'job_id' => LibraryJobId::EXTRACT_SUBTITLES],
        );

        LibraryJob::firstOrCreate(
            ['library_id' => $library->id, 'job_id' => LibraryJobId::CONVERT_SUBTITLE],
        );

        $worker = Worker::firstOrCreate(['name' => 'browser-test-worker']);

        Execution::firstOrCreate(
            ['library_job_id' => $transcodeJob->id, 'file_path' => '/tmp/test.mkv'],
            [
                'worker_id' => (string) $worker->id,
                'status' => ExecutionStatus::COMPLETED,
                'started_at' => now()->subHour(),
                'finished_at' => now()->subMinutes(30),
            ],
        );
    }
}
