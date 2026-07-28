<?php

use App\ExecutionStatus;
use App\LibraryJobId;
use App\Models\Execution;
use App\Models\Library;
use App\Models\LibraryJob;
use App\Models\User;
use App\Models\Worker;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('lists workers', function () {
    Worker::factory()->count(2)->create();

    $this->get('/workers')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('workers/index'));
});

it('shows worker detail', function () {
    $worker = Worker::factory()->create();

    $this->get("/workers/{$worker->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('workers/[id]/index'));
});

it('creates a worker', function () {
    $this->post('/workers', [
        'name' => 'Test Worker',
        'job_type' => LibraryJobId::TRANSCODE_MEDIA->value,
        'concurrency' => 2,
    ])->assertRedirect('/workers');

    $this->assertDatabaseHas('workers', [
        'name' => 'Test Worker',
        'job_type' => LibraryJobId::TRANSCODE_MEDIA->value,
        'concurrency' => 2,
    ]);
});

it('validates worker creation', function () {
    $this->post('/workers', [])
        ->assertSessionHasErrors(['name', 'job_type', 'concurrency']);
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

it('deletes a worker', function () {
    $worker = Worker::factory()->create();

    $this->delete("/workers/{$worker->id}")
        ->assertRedirect('/workers');

    $this->assertDatabaseMissing('workers', ['id' => $worker->id]);
});

it('starts executions for a worker type', function () {
    $library = Library::factory()->create();
    $job = LibraryJob::factory()->create([
        'library_id' => $library->id,
        'job_id' => LibraryJobId::TRANSCODE_MEDIA,
    ]);
    $execution = Execution::factory()->create([
        'library_job_id' => $job->id,
        'status' => ExecutionStatus::QUEUED,
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

it('starts all queued and paused executions', function () {
    $e1 = Execution::factory()->create(['status' => ExecutionStatus::QUEUED]);
    $e2 = Execution::factory()->create(['status' => ExecutionStatus::PAUSED]);
    $e3 = Execution::factory()->create(['status' => ExecutionStatus::COMPLETED]);

    $this->post('/workers/start-all')
        ->assertRedirect();

    $this->assertDatabaseHas('executions', ['id' => $e1->id, 'status' => ExecutionStatus::PROCESSING]);
    $this->assertDatabaseHas('executions', ['id' => $e2->id, 'status' => ExecutionStatus::PROCESSING]);
    $this->assertDatabaseHas('executions', ['id' => $e3->id, 'status' => ExecutionStatus::COMPLETED]);
});

it('pauses all processing executions', function () {
    $e1 = Execution::factory()->create(['status' => ExecutionStatus::PROCESSING]);
    $e2 = Execution::factory()->create(['status' => ExecutionStatus::QUEUED]);

    $this->post('/workers/pause-all')
        ->assertRedirect();

    $this->assertDatabaseHas('executions', ['id' => $e1->id, 'status' => ExecutionStatus::PAUSED]);
    $this->assertDatabaseHas('executions', ['id' => $e2->id, 'status' => ExecutionStatus::QUEUED]);
});

it('resumes all paused executions', function () {
    $e1 = Execution::factory()->create(['status' => ExecutionStatus::PAUSED]);
    $e2 = Execution::factory()->create(['status' => ExecutionStatus::QUEUED]);

    $this->post('/workers/resume-all')
        ->assertRedirect();

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
