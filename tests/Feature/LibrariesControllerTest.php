<?php

use App\ExecutionStatus;
use App\Jobs\ScanLibrary;
use App\LibraryJobId;
use App\Models\Execution;
use App\Models\Library;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('lists libraries', function () {
    Library::factory()->count(3)->create();

    $this->get('/libraries')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('libraries/index'));
});

it('shows create form', function () {
    $this->get('/libraries/create')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('libraries/create'));
});

it('stores a new library', function () {
    $dir = sys_get_temp_dir().'/lib-test-'.uniqid();
    mkdir($dir, 0755, true);

    $response = $this->post('/libraries', [
        'base_path' => $dir,
        'scan_interval' => 3600,
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('libraries', [
        'base_path' => $dir,
        'scan_interval' => 3600,
    ]);
});

it('attaches every worker to a new library by default', function () {
    Queue::fake();
    $dir = sys_get_temp_dir().'/lib-test-'.uniqid();
    mkdir($dir, 0755, true);

    $this->post('/libraries', ['base_path' => $dir, 'scan_interval' => 3600])->assertRedirect();

    $library = Library::where('base_path', $dir)->firstOrFail();
    expect($library->workers()->count())->toBe(Worker::count());
    Queue::assertPushed(ScanLibrary::class, fn (ScanLibrary $job) => $job->libraryId === $library->id);
});

it('attaches only the selected workers to a new library', function () {
    Queue::fake();
    $dir = sys_get_temp_dir().'/lib-test-'.uniqid();
    mkdir($dir, 0755, true);
    $worker = Worker::where('job_type', LibraryJobId::TRANSCODE_MEDIA)->firstOrFail();

    $this->post('/libraries', ['base_path' => $dir, 'scan_interval' => 3600, 'worker_ids' => [$worker->id]])
        ->assertRedirect();

    expect(Library::where('base_path', $dir)->firstOrFail()->workers->pluck('id')->all())->toBe([$worker->id]);
});

it('validates required fields on store', function () {
    $this->post('/libraries', [])
        ->assertSessionHasErrors(['base_path', 'scan_interval']);
});

it('validates base_path is a real directory on store', function () {
    $this->post('/libraries', [
        'base_path' => '/nonexistent-path-98765',
        'scan_interval' => 3600,
    ])->assertSessionHasErrors(['base_path']);
});

it('shows library detail', function () {
    $library = Library::factory()->create();

    $this->get("/libraries/{$library->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('libraries/[id]/index'));
});

it('updates a library', function () {
    $library = Library::factory()->create();
    $dir = sys_get_temp_dir().'/lib-update-'.uniqid();
    mkdir($dir, 0755, true);

    $this->patch("/libraries/{$library->id}", [
        'base_path' => $dir,
        'scan_interval' => 7200,
    ])->assertRedirect("/libraries/{$library->id}");

    $this->assertDatabaseHas('libraries', [
        'id' => $library->id,
        'base_path' => $dir,
        'scan_interval' => 7200,
    ]);
});

it('deletes a library', function () {
    $library = Library::factory()->create();
    $library->libraryJobs()->create(['job_id' => LibraryJobId::TRANSCODE_MEDIA]);

    $this->delete("/libraries/{$library->id}")
        ->assertRedirect('/libraries');

    $this->assertDatabaseMissing('libraries', ['id' => $library->id]);
    $this->assertDatabaseMissing('library_jobs', ['library_id' => $library->id]);
});

it('cascades delete to executions when library is deleted', function () {
    $library = Library::factory()->create();
    $libraryJob = $library->libraryJobs()->create(['job_id' => LibraryJobId::TRANSCODE_MEDIA]);
    $execution = Execution::factory()->create([
        'library_job_id' => $libraryJob->id,
        'status' => ExecutionStatus::QUEUED,
    ]);

    $this->delete("/libraries/{$library->id}")
        ->assertRedirect('/libraries');

    $this->assertDatabaseMissing('libraries', ['id' => $library->id]);
    $this->assertDatabaseMissing('library_jobs', ['id' => $libraryJob->id]);
    $this->assertDatabaseMissing('executions', ['id' => $execution->id]);
});

it('triggers scan', function () {
    Queue::fake();
    $library = Library::factory()->create();

    $this->post("/libraries/{$library->id}/scan")
        ->assertRedirect("/libraries/{$library->id}");

    $this->assertDatabaseHas('libraries', [
        'id' => $library->id,
        'status' => 'pending_scan',
    ]);
    Queue::assertPushed(ScanLibrary::class);
});

it('toggles a job on', function () {
    $library = Library::factory()->create();

    $this->post("/libraries/{$library->id}/toggle-job", [
        'job_id' => 'transcode_media',
        'enabled' => true,
    ])->assertRedirect("/libraries/{$library->id}");

    $this->assertDatabaseHas('library_jobs', [
        'library_id' => $library->id,
        'job_id' => 'transcode_media',
    ]);
});

it('toggles a job off', function () {
    $library = Library::factory()->create();
    $library->libraryJobs()->create(['job_id' => LibraryJobId::TRANSCODE_MEDIA]);

    $this->post("/libraries/{$library->id}/toggle-job", [
        'job_id' => 'transcode_media',
        'enabled' => false,
    ])->assertRedirect("/libraries/{$library->id}");

    $this->assertDatabaseMissing('library_jobs', [
        'library_id' => $library->id,
        'job_id' => 'transcode_media',
    ]);
});

it('toggles a worker on for a library', function () {
    $library = Library::factory()->create();
    $worker = Worker::factory()->create();

    $this->post("/libraries/{$library->id}/toggle-worker", [
        'worker_id' => $worker->id,
        'enabled' => true,
    ])->assertRedirect();

    $this->assertDatabaseHas('library_worker', [
        'library_id' => $library->id,
        'worker_id' => $worker->id,
    ]);
});

it('toggles a worker off for a library', function () {
    $library = Library::factory()->create();
    $worker = Worker::factory()->create();
    $library->workers()->attach($worker);

    $this->post("/libraries/{$library->id}/toggle-worker", [
        'worker_id' => $worker->id,
        'enabled' => false,
    ])->assertRedirect();

    $this->assertDatabaseMissing('library_worker', [
        'library_id' => $library->id,
        'worker_id' => $worker->id,
    ]);
});
