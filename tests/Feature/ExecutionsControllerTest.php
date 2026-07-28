<?php

use App\ExecutionStatus;
use App\Models\Execution;
use App\Models\User;

beforeEach(function () {
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

it('retries a failed execution', function () {
    $execution = Execution::factory()->create(['status' => ExecutionStatus::FAILED]);

    $this->post("/executions/{$execution->id}/retry")
        ->assertRedirect();

    $this->assertDatabaseHas('executions', [
        'library_job_id' => $execution->library_job_id,
        'file_path' => $execution->file_path,
        'status' => ExecutionStatus::QUEUED,
    ]);
});

it('retry only works on failed executions', function () {
    $execution = Execution::factory()->create(['status' => ExecutionStatus::COMPLETED]);

    $this->post("/executions/{$execution->id}/retry")
        ->assertRedirect();
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

it('starts a queued execution', function () {
    $execution = Execution::factory()->create(['status' => ExecutionStatus::QUEUED]);

    $this->post("/executions/{$execution->id}/start")
        ->assertRedirect();

    $this->assertDatabaseHas('executions', [
        'id' => $execution->id,
        'status' => ExecutionStatus::PROCESSING,
    ]);
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

it('start only works on queued or paused', function () {
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
    $e1 = Execution::factory()->create(['status' => ExecutionStatus::QUEUED]);
    $e2 = Execution::factory()->create(['status' => ExecutionStatus::PAUSED]);
    $e3 = Execution::factory()->create(['status' => ExecutionStatus::COMPLETED]);

    $this->post('/executions/batch/start', [
        'ids' => [$e1->id, $e2->id, $e3->id],
    ])->assertRedirect();

    $this->assertDatabaseHas('executions', ['id' => $e1->id, 'status' => ExecutionStatus::PROCESSING]);
    $this->assertDatabaseHas('executions', ['id' => $e2->id, 'status' => ExecutionStatus::PROCESSING]);
    $this->assertDatabaseHas('executions', ['id' => $e3->id, 'status' => ExecutionStatus::COMPLETED]);
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
