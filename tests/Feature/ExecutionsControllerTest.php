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
