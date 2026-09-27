<?php

use App\ExecutionStatus;
use App\Jobs\TranscodeMedia;
use App\LibraryJobId;
use App\Models\Execution;
use App\Models\LibraryJob;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('lists executions', function () {
    Execution::factory()->count(3)->create();

    $this->get('/executions')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('executions/index'));
});

it('filters executions by status', function () {
    Execution::factory()->create(['status' => ExecutionStatus::FAILED]);
    Execution::factory()->create(['status' => ExecutionStatus::COMPLETED]);

    $this->get('/executions?status=failed')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('executions/index')
            ->has('executions.data', 1)
        );
});

it('shows execution detail', function () {
    $execution = Execution::factory()->create();

    $this->get("/executions/{$execution->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('executions/[id]/index'));
});

it('filters executions by file path search', function () {
    Execution::factory()->create(['file_path' => '/media/Movies/Alien.mkv']);
    Execution::factory()->create(['file_path' => '/media/Movies/Heat.mkv']);

    $this->get('/executions?search=alien')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('executions.data', 1)
            ->where('executions.data.0.file_path', '/media/Movies/Alien.mkv'));
});

it('rejects unknown status filters', function () {
    $this->get('/executions?status=bogus')->assertSessionHasErrors('status');
});

it('includes output and duration on the detail page', function () {
    $execution = Execution::factory()->create([
        'status' => ExecutionStatus::FAILED,
        'output' => "ffmpeg error\n",
        'message' => 'Process exited with code 1',
        'started_at' => now()->subMinutes(2),
        'finished_at' => now()->subMinute(),
    ]);

    $this->get("/executions/{$execution->id}")
        ->assertInertia(fn ($page) => $page
            ->where('execution.output', "ffmpeg error\n")
            ->where('execution.message', 'Process exited with code 1')
            ->where('execution.duration_seconds', 60));
});

it('retries a failed execution by re-queueing it', function () {
    $job = LibraryJob::factory()->create(['job_id' => LibraryJobId::TRANSCODE_MEDIA]);
    $execution = Execution::factory()->create([
        'library_job_id' => $job->id,
        'status' => ExecutionStatus::FAILED,
        'message' => 'boom',
        'progress' => 42,
        'finished_at' => now(),
    ]);

    $this->post("/executions/{$execution->id}/retry")
        ->assertRedirect();

    $execution->refresh();
    expect($execution->status)->toBe(ExecutionStatus::QUEUED)
        ->and($execution->message)->toBeNull()
        ->and($execution->progress)->toBeNull()
        ->and($execution->finished_at)->toBeNull();

    Queue::assertPushed(TranscodeMedia::class, fn (TranscodeMedia $job) => $job->execution->is($execution));
});

it('retries a stopped execution', function () {
    $execution = Execution::factory()->create(['status' => ExecutionStatus::STOPPED]);

    $this->post("/executions/{$execution->id}/retry")->assertRedirect();

    expect($execution->refresh()->status)->toBe(ExecutionStatus::QUEUED);
});

it('retry does not touch completed executions', function () {
    $execution = Execution::factory()->create(['status' => ExecutionStatus::COMPLETED]);

    $this->post("/executions/{$execution->id}/retry")
        ->assertRedirect();

    expect($execution->refresh()->status)->toBe(ExecutionStatus::COMPLETED);
    Queue::assertNothingPushed();
});

it('cancels a queued execution', function () {
    $execution = Execution::factory()->create(['status' => ExecutionStatus::QUEUED]);

    $this->post("/executions/{$execution->id}/cancel")
        ->assertRedirect();

    $this->assertDatabaseHas('executions', [
        'id' => $execution->id,
        'status' => ExecutionStatus::STOPPED,
    ]);
});

it('cancel only works on queued or processing', function () {
    $execution = Execution::factory()->create(['status' => ExecutionStatus::COMPLETED]);

    $this->post("/executions/{$execution->id}/cancel")
        ->assertRedirect();

    $this->assertDatabaseHas('executions', [
        'id' => $execution->id,
        'status' => ExecutionStatus::COMPLETED,
    ]);
});

it('start re-queues a stopped execution', function () {
    $execution = Execution::factory()->create(['status' => ExecutionStatus::STOPPED]);

    $this->post("/executions/{$execution->id}/start")
        ->assertRedirect();

    $this->assertDatabaseHas('executions', [
        'id' => $execution->id,
        'status' => ExecutionStatus::QUEUED,
    ]);
});

it('start leaves queued executions waiting in the queue', function () {
    $execution = Execution::factory()->create(['status' => ExecutionStatus::QUEUED]);

    $this->post("/executions/{$execution->id}/start")->assertRedirect();

    expect($execution->refresh()->status)->toBe(ExecutionStatus::QUEUED);
});

it('starts a paused execution', function () {
    $execution = Execution::factory()->create(['status' => ExecutionStatus::PAUSED]);

    $this->post("/executions/{$execution->id}/start")
        ->assertRedirect();

    $this->assertDatabaseHas('executions', [
        'id' => $execution->id,
        'status' => ExecutionStatus::PROCESSING,
    ]);
});

it('start does not touch completed executions', function () {
    $execution = Execution::factory()->create(['status' => ExecutionStatus::COMPLETED]);

    $this->post("/executions/{$execution->id}/start")
        ->assertRedirect();

    $this->assertDatabaseHas('executions', [
        'id' => $execution->id,
        'status' => ExecutionStatus::COMPLETED,
    ]);
});

it('pauses a processing execution', function () {
    $execution = Execution::factory()->create(['status' => ExecutionStatus::PROCESSING]);

    $this->post("/executions/{$execution->id}/pause")
        ->assertRedirect();

    $this->assertDatabaseHas('executions', [
        'id' => $execution->id,
        'status' => ExecutionStatus::PAUSED,
    ]);
});

it('pause only works on processing', function () {
    $execution = Execution::factory()->create(['status' => ExecutionStatus::QUEUED]);

    $this->post("/executions/{$execution->id}/pause")
        ->assertRedirect();

    $this->assertDatabaseHas('executions', [
        'id' => $execution->id,
        'status' => ExecutionStatus::QUEUED,
    ]);
});

it('resumes a paused execution', function () {
    $execution = Execution::factory()->create(['status' => ExecutionStatus::PAUSED]);

    $this->post("/executions/{$execution->id}/resume")
        ->assertRedirect();

    $this->assertDatabaseHas('executions', [
        'id' => $execution->id,
        'status' => ExecutionStatus::PROCESSING,
    ]);
});

it('resume only works on paused', function () {
    $execution = Execution::factory()->create(['status' => ExecutionStatus::QUEUED]);

    $this->post("/executions/{$execution->id}/resume")
        ->assertRedirect();

    $this->assertDatabaseHas('executions', [
        'id' => $execution->id,
        'status' => ExecutionStatus::QUEUED,
    ]);
});

it('stops a queued execution', function () {
    $execution = Execution::factory()->create(['status' => ExecutionStatus::QUEUED]);

    $this->post("/executions/{$execution->id}/stop")
        ->assertRedirect();

    $this->assertDatabaseHas('executions', [
        'id' => $execution->id,
        'status' => ExecutionStatus::STOPPED,
    ]);
});

it('stops a processing execution', function () {
    $execution = Execution::factory()->create(['status' => ExecutionStatus::PROCESSING]);

    $this->post("/executions/{$execution->id}/stop")
        ->assertRedirect();

    $this->assertDatabaseHas('executions', [
        'id' => $execution->id,
        'status' => ExecutionStatus::STOPPED,
    ]);
});

it('stops a paused execution', function () {
    $execution = Execution::factory()->create(['status' => ExecutionStatus::PAUSED]);

    $this->post("/executions/{$execution->id}/stop")
        ->assertRedirect();

    $this->assertDatabaseHas('executions', [
        'id' => $execution->id,
        'status' => ExecutionStatus::STOPPED,
    ]);
});

it('delete removes an execution record', function () {
    $execution = Execution::factory()->create();

    $this->delete("/executions/{$execution->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('executions', ['id' => $execution->id]);
});

it('batch starts executions', function () {
    $e1 = Execution::factory()->create(['status' => ExecutionStatus::FAILED]);
    $e2 = Execution::factory()->create(['status' => ExecutionStatus::PAUSED]);
    $e3 = Execution::factory()->create(['status' => ExecutionStatus::COMPLETED]);

    $this->post('/executions/batch/start', [
        'ids' => [$e1->id, $e2->id, $e3->id],
    ])->assertRedirect();

    $this->assertDatabaseHas('executions', ['id' => $e1->id, 'status' => ExecutionStatus::QUEUED]);
    $this->assertDatabaseHas('executions', ['id' => $e2->id, 'status' => ExecutionStatus::PROCESSING]);
    $this->assertDatabaseHas('executions', ['id' => $e3->id, 'status' => ExecutionStatus::COMPLETED]);
});

it('batch retries failed executions', function () {
    $e1 = Execution::factory()->create(['status' => ExecutionStatus::FAILED]);
    $e2 = Execution::factory()->create(['status' => ExecutionStatus::PROCESSING]);

    $this->post('/executions/batch/retry', ['ids' => [$e1->id, $e2->id]])->assertRedirect();

    $this->assertDatabaseHas('executions', ['id' => $e1->id, 'status' => ExecutionStatus::QUEUED]);
    $this->assertDatabaseHas('executions', ['id' => $e2->id, 'status' => ExecutionStatus::PROCESSING]);
});

it('validates batch ids', function () {
    $this->post('/executions/batch/stop', ['ids' => 'nope'])->assertSessionHasErrors('ids');
});

it('batch pauses executions', function () {
    $e1 = Execution::factory()->create(['status' => ExecutionStatus::PROCESSING]);
    $e2 = Execution::factory()->create(['status' => ExecutionStatus::QUEUED]);

    $this->post('/executions/batch/pause', [
        'ids' => [$e1->id, $e2->id],
    ])->assertRedirect();

    $this->assertDatabaseHas('executions', ['id' => $e1->id, 'status' => ExecutionStatus::PAUSED]);
    $this->assertDatabaseHas('executions', ['id' => $e2->id, 'status' => ExecutionStatus::QUEUED]);
});

it('batch resumes executions', function () {
    $e1 = Execution::factory()->create(['status' => ExecutionStatus::PAUSED]);
    $e2 = Execution::factory()->create(['status' => ExecutionStatus::COMPLETED]);

    $this->post('/executions/batch/resume', [
        'ids' => [$e1->id, $e2->id],
    ])->assertRedirect();

    $this->assertDatabaseHas('executions', ['id' => $e1->id, 'status' => ExecutionStatus::PROCESSING]);
    $this->assertDatabaseHas('executions', ['id' => $e2->id, 'status' => ExecutionStatus::COMPLETED]);
});

it('batch stops executions', function () {
    $e1 = Execution::factory()->create(['status' => ExecutionStatus::QUEUED]);
    $e2 = Execution::factory()->create(['status' => ExecutionStatus::PROCESSING]);
    $e3 = Execution::factory()->create(['status' => ExecutionStatus::COMPLETED]);

    $this->post('/executions/batch/stop', [
        'ids' => [$e1->id, $e2->id, $e3->id],
    ])->assertRedirect();

    $this->assertDatabaseHas('executions', ['id' => $e1->id, 'status' => ExecutionStatus::STOPPED]);
    $this->assertDatabaseHas('executions', ['id' => $e2->id, 'status' => ExecutionStatus::STOPPED]);
    $this->assertDatabaseHas('executions', ['id' => $e3->id, 'status' => ExecutionStatus::COMPLETED]);
});

it('batch deletes executions', function () {
    $e1 = Execution::factory()->create();
    $e2 = Execution::factory()->create();

    $this->post('/executions/batch/delete', [
        'ids' => [$e1->id, $e2->id],
    ])->assertRedirect();

    $this->assertDatabaseMissing('executions', ['id' => $e1->id]);
    $this->assertDatabaseMissing('executions', ['id' => $e2->id]);
});
