<?php

use App\ExecutionStatus;
use App\Models\Execution;

it('fails processing executions whose worker stopped sending heartbeats', function () {
    $stale = Execution::factory()->create(['status' => ExecutionStatus::PROCESSING, 'heartbeat_at' => now()->subMinutes(10)]);
    $stalePaused = Execution::factory()->create(['status' => ExecutionStatus::PAUSED, 'heartbeat_at' => now()->subMinutes(10)]);
    $alive = Execution::factory()->create(['status' => ExecutionStatus::PROCESSING, 'heartbeat_at' => now()->subSeconds(30)]);
    $queued = Execution::factory()->create(['status' => ExecutionStatus::QUEUED, 'heartbeat_at' => null]);

    $this->artisan('executions:reap')->assertSuccessful();

    expect($stale->refresh()->status)->toBe(ExecutionStatus::FAILED)
        ->and($stale->message)->toContain('stopped responding')
        ->and($stalePaused->refresh()->status)->toBe(ExecutionStatus::FAILED)
        ->and($alive->refresh()->status)->toBe(ExecutionStatus::PROCESSING)
        ->and($queued->refresh()->status)->toBe(ExecutionStatus::QUEUED);
});

it('is scheduled every minute', function () {
    $this->artisan('schedule:list')->expectsOutputToContain('executions:reap');
});
