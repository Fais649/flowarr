<?php

use App\ExecutionStatus;
use App\LibraryJobId;
use App\LibraryStatus;
use App\Models\Execution;
use App\Models\Library;
use App\Models\Setting;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->user = User::factory()->create();

    $this->mediaDir = storage_path('app/testing_scan_'.uniqid());
    File::makeDirectory($this->mediaDir, 0777, true, true);
    $this->videoFile = $this->mediaDir.'/video.mkv';

    $ffmpeg = config('services.ffmpeg.bin', 'ffmpeg');
    exec("{$ffmpeg} -y -f lavfi -i color=c=black:s=320x240:d=1 -t 1 -c:v libx264 -pix_fmt yuv420p {$this->videoFile} 2>&1 >/dev/null");
});

afterEach(function () {
    File::deleteDirectory($this->mediaDir);
});

it('creates executions for files needing transcode', function () {
    $library = Library::factory()->create([
        'base_path' => $this->mediaDir,
        'status' => LibraryStatus::PENDING_SCAN,
    ]);
    $worker = Worker::factory()->create([
        'job_type' => LibraryJobId::TRANSCODE_MEDIA,
        'enabled' => true,
    ]);
    $library->workers()->attach($worker);

    $this->artisan('scan:libraries')->assertSuccessful();

    $executions = Execution::whereHas('libraryJob', fn ($q) => $q->where('library_id', $library->id))->get();
    expect($executions)->not->toBeEmpty();
});

it('skips libraries without enabled jobs', function () {
    $library = Library::factory()->create([
        'base_path' => $this->mediaDir,
        'status' => LibraryStatus::PENDING_SCAN,
    ]);

    $this->artisan('scan:libraries')->assertSuccessful();

    expect(Execution::count())->toBe(0);
});

it('skips files that already have queued executions', function () {
    $library = Library::factory()->create([
        'base_path' => $this->mediaDir,
        'status' => LibraryStatus::PENDING_SCAN,
    ]);
    $worker = Worker::factory()->create([
        'job_type' => LibraryJobId::TRANSCODE_MEDIA,
        'enabled' => true,
    ]);
    $library->workers()->attach($worker);
    $libraryJob = $library->libraryJobs()->create(['job_id' => LibraryJobId::TRANSCODE_MEDIA]);
    Execution::factory()->create([
        'library_job_id' => $libraryJob->id,
        'file_path' => $this->mediaDir.'/video.mkv',
        'status' => ExecutionStatus::QUEUED,
    ]);

    $this->artisan('scan:libraries')->assertSuccessful();

    $executions = Execution::where('library_job_id', $libraryJob->id)->get();
    expect($executions)->toHaveCount(1);
});

it('updates library last_scan after scanning', function () {
    $library = Library::factory()->create([
        'base_path' => $this->mediaDir,
        'status' => LibraryStatus::PENDING_SCAN,
        'last_scan' => null,
    ]);
    $worker = Worker::factory()->create([
        'job_type' => LibraryJobId::TRANSCODE_MEDIA,
        'enabled' => true,
    ]);
    $library->workers()->attach($worker);
    $libraryJob = $library->libraryJobs()->create(['job_id' => LibraryJobId::TRANSCODE_MEDIA]);

    $exitCode = $this->artisan('scan:libraries')->run();
    expect($exitCode)->toBe(0);

    $library->refresh();
    expect($library->last_scan)->not->toBeNull();
    expect(Execution::where('library_job_id', $libraryJob->id)->count())->toBeGreaterThanOrEqual(1);
});

it('scans PENDING libraries whose interval has elapsed', function () {
    $library = Library::factory()->create([
        'base_path' => $this->mediaDir,
        'status' => LibraryStatus::PENDING,
        'last_scan' => now()->subHours(2),
        'scan_interval' => 60,
    ]);
    $worker = Worker::factory()->create([
        'job_type' => LibraryJobId::TRANSCODE_MEDIA,
        'enabled' => true,
    ]);
    $library->workers()->attach($worker);

    $this->artisan('scan:libraries')->assertSuccessful();

    $executions = Execution::whereHas(
        'libraryJob', fn ($q) => $q->where('library_id', $library->id)
    )->get();
    expect($executions)->not->toBeEmpty();
});

it('skips PENDING libraries whose interval has not elapsed', function () {
    $library = Library::factory()->create([
        'base_path' => $this->mediaDir,
        'status' => LibraryStatus::PENDING,
        'last_scan' => now(),
        'scan_interval' => 3600,
    ]);
    $worker = Worker::factory()->create([
        'job_type' => LibraryJobId::TRANSCODE_MEDIA,
        'enabled' => true,
    ]);
    $library->workers()->attach($worker);

    $this->artisan('scan:libraries')->assertSuccessful();

    $executions = Execution::whereHas(
        'libraryJob', fn ($q) => $q->where('library_id', $library->id)
    )->get();
    expect($executions)->toBeEmpty();
});

it('respects scan concurrency limit', function () {
    Setting::set('scan.concurrency', '1');

    $library1 = Library::factory()->create([
        'base_path' => $this->mediaDir,
        'status' => LibraryStatus::PENDING_SCAN,
        'last_scan' => null,
    ]);
    $worker = Worker::factory()->create([
        'job_type' => LibraryJobId::TRANSCODE_MEDIA,
        'enabled' => true,
    ]);
    $library1->workers()->attach($worker);

    $dir2 = storage_path('app/testing_scan_concurrency_'.uniqid());
    File::makeDirectory($dir2, 0777, true, true);
    $ffmpeg = config('services.ffmpeg.bin', 'ffmpeg');
    exec("{$ffmpeg} -y -f lavfi -i color=c=black:s=320x240:d=1 -t 1 -c:v libx264 {$dir2}/video.mkv 2>&1 >/dev/null");

    $library2 = Library::factory()->create([
        'base_path' => $dir2,
        'status' => LibraryStatus::PENDING_SCAN,
        'last_scan' => null,
    ]);
    $library2->workers()->attach($worker);

    $this->artisan('scan:libraries')->assertSuccessful();

    $library1->refresh();
    $library2->refresh();

    $scannedCount = 0;
    if ($library1->last_scan !== null) {
        $scannedCount++;
    }
    if ($library2->last_scan !== null) {
        $scannedCount++;
    }

    expect($scannedCount)->toBe(1);

    File::deleteDirectory($dir2);
});

it('scans PENDING_SCAN libraries regardless of interval', function () {
    $library = Library::factory()->create([
        'base_path' => $this->mediaDir,
        'status' => LibraryStatus::PENDING_SCAN,
        'last_scan' => now(),
        'scan_interval' => 3600,
    ]);
    $worker = Worker::factory()->create([
        'job_type' => LibraryJobId::TRANSCODE_MEDIA,
        'enabled' => true,
    ]);
    $library->workers()->attach($worker);

    $this->artisan('scan:libraries')->assertSuccessful();

    $executions = Execution::whereHas(
        'libraryJob', fn ($q) => $q->where('library_id', $library->id)
    )->get();
    expect($executions)->not->toBeEmpty();
});
