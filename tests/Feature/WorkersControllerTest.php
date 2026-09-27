<?php

use App\ExecutionStatus;
use App\LibraryJobId;
use App\Models\Execution;
use App\Models\Library;
use App\Models\LibraryJob;
use App\Models\User;
use App\Models\Worker;
use App\Services\ProcessingGate;
use App\Services\StreamTracker;
use App\Settings;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('lists workers', function () {
    $this->get('/workers')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('workers/index')
            // One default worker per job type is created by migration.
            ->has('workers', 3)
            ->has('workers.0.queued_count')
            ->where('maxConcurrency', Worker::MAX_CONCURRENCY)
            ->where('processing.paused', false)
            ->missing('capabilities'));
});

it('loads hardware capabilities as a deferred prop', function () {
    $this->get('/workers')
        ->assertInertia(fn ($page) => $page->loadDeferredProps(fn ($reload) => $reload->has('capabilities.encoders')));
});

it('rejects concurrency above the process pool size', function () {
    $worker = Worker::first();

    $this->patch("/workers/{$worker->id}", ['concurrency' => Worker::MAX_CONCURRENCY + 1])
        ->assertSessionHasErrors('concurrency');
});

it('shows worker detail', function () {
    $worker = Worker::factory()->create();

    $this->get("/workers/{$worker->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('workers/[id]/index'));
});

it('updates a worker', function () {
    $worker = Worker::factory()->create();

    $this->patch("/workers/{$worker->id}", [
        'name' => 'Updated Name',
        'concurrency' => 5,
    ])->assertRedirect();

    $this->assertDatabaseHas('workers', [
        'id' => $worker->id,
        'name' => 'Updated Name',
        'concurrency' => 5,
    ]);
});

it('starts executions for a worker type', function () {
    $library = Library::factory()->create();
    $job = LibraryJob::factory()->create([
        'library_id' => $library->id,
        'job_id' => LibraryJobId::TRANSCODE_MEDIA,
    ]);
    $execution = Execution::factory()->create([
        'library_job_id' => $job->id,
        'status' => ExecutionStatus::PAUSED,
    ]);
    $worker = Worker::factory()->create([
        'job_type' => LibraryJobId::TRANSCODE_MEDIA,
    ]);

    $this->post("/workers/{$worker->id}/start")
        ->assertRedirect();

    $this->assertDatabaseHas('executions', [
        'id' => $execution->id,
        'status' => ExecutionStatus::PROCESSING,
    ]);
});

it('pauses processing executions for a worker type', function () {
    $library = Library::factory()->create();
    $job = LibraryJob::factory()->create([
        'library_id' => $library->id,
        'job_id' => LibraryJobId::TRANSCODE_MEDIA,
    ]);
    $execution = Execution::factory()->create([
        'library_job_id' => $job->id,
        'status' => ExecutionStatus::PROCESSING,
    ]);
    $worker = Worker::factory()->create([
        'job_type' => LibraryJobId::TRANSCODE_MEDIA,
    ]);

    $this->post("/workers/{$worker->id}/pause")
        ->assertRedirect();

    $this->assertDatabaseHas('executions', [
        'id' => $execution->id,
        'status' => ExecutionStatus::PAUSED,
    ]);
});

it('resumes paused executions for a worker type', function () {
    $library = Library::factory()->create();
    $job = LibraryJob::factory()->create([
        'library_id' => $library->id,
        'job_id' => LibraryJobId::TRANSCODE_MEDIA,
    ]);
    $execution = Execution::factory()->create([
        'library_job_id' => $job->id,
        'status' => ExecutionStatus::PAUSED,
    ]);
    $worker = Worker::factory()->create([
        'job_type' => LibraryJobId::TRANSCODE_MEDIA,
    ]);

    $this->post("/workers/{$worker->id}/resume")
        ->assertRedirect();

    $this->assertDatabaseHas('executions', [
        'id' => $execution->id,
        'status' => ExecutionStatus::PROCESSING,
    ]);
});

it('stops executions for a worker type', function () {
    $library = Library::factory()->create();
    $job = LibraryJob::factory()->create([
        'library_id' => $library->id,
        'job_id' => LibraryJobId::TRANSCODE_MEDIA,
    ]);
    $execution = Execution::factory()->create([
        'library_job_id' => $job->id,
        'status' => ExecutionStatus::PROCESSING,
    ]);
    $worker = Worker::factory()->create([
        'job_type' => LibraryJobId::TRANSCODE_MEDIA,
    ]);

    $this->post("/workers/{$worker->id}/stop")
        ->assertRedirect();

    $this->assertDatabaseHas('executions', [
        'id' => $execution->id,
        'status' => ExecutionStatus::STOPPED,
    ]);
});

it('start all lifts a manual pause and resumes paused executions', function () {
    app(ProcessingGate::class)->pause();
    $e1 = Execution::factory()->create(['status' => ExecutionStatus::QUEUED]);
    $e2 = Execution::factory()->create(['status' => ExecutionStatus::PAUSED]);
    $e3 = Execution::factory()->create(['status' => ExecutionStatus::COMPLETED]);

    $this->post('/workers/start-all')
        ->assertRedirect();

    expect(Settings::isProcessingPaused())->toBeFalse();
    $this->assertDatabaseHas('executions', ['id' => $e1->id, 'status' => ExecutionStatus::QUEUED]);
    $this->assertDatabaseHas('executions', ['id' => $e2->id, 'status' => ExecutionStatus::PROCESSING]);
    $this->assertDatabaseHas('executions', ['id' => $e3->id, 'status' => ExecutionStatus::COMPLETED]);
});

it('pause all holds processing through the processing gate', function () {
    $this->post('/workers/pause-all')
        ->assertRedirect();

    expect(Settings::isProcessingPaused())->toBeTrue()
        ->and(app(ProcessingGate::class)->pauseReasons())->toBe([ProcessingGate::REASON_MANUAL]);
});

it('clears tracked streams', function () {
    app(StreamTracker::class)->start('jellyfin', 'abc');

    $this->delete('/workers/streams')->assertRedirect();

    expect(app(StreamTracker::class)->count())->toBe(0);
});

it('resumes all paused executions', function () {
    app(ProcessingGate::class)->pause();
    $e1 = Execution::factory()->create(['status' => ExecutionStatus::PAUSED]);
    $e2 = Execution::factory()->create(['status' => ExecutionStatus::QUEUED]);

    $this->post('/workers/resume-all')
        ->assertRedirect();

    expect(Settings::isProcessingPaused())->toBeFalse();

    $this->assertDatabaseHas('executions', ['id' => $e1->id, 'status' => ExecutionStatus::PROCESSING]);
    $this->assertDatabaseHas('executions', ['id' => $e2->id, 'status' => ExecutionStatus::QUEUED]);
});

it('stops all active executions', function () {
    $e1 = Execution::factory()->create(['status' => ExecutionStatus::QUEUED]);
    $e2 = Execution::factory()->create(['status' => ExecutionStatus::PROCESSING]);
    $e3 = Execution::factory()->create(['status' => ExecutionStatus::COMPLETED]);

    $this->post('/workers/stop-all')
        ->assertRedirect();

    $this->assertDatabaseHas('executions', ['id' => $e1->id, 'status' => ExecutionStatus::STOPPED]);
    $this->assertDatabaseHas('executions', ['id' => $e2->id, 'status' => ExecutionStatus::STOPPED]);
    $this->assertDatabaseHas('executions', ['id' => $e3->id, 'status' => ExecutionStatus::COMPLETED]);
});
