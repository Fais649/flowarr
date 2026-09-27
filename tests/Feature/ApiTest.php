<?php

use App\ExecutionStatus;
use App\Jobs\ScanLibrary;
use App\Models\Execution;
use App\Models\Library;
use App\Models\Setting;
use App\Settings;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
    $this->token = 'flw_test_token';
    Setting::set(Settings::API_TOKEN_HASH, hash('sha256', $this->token));
});

function api(): array
{
    return ['Authorization' => 'Bearer flw_test_token', 'Accept' => 'application/json'];
}

it('rejects requests without a valid token', function () {
    $this->getJson('/api/v1/status')->assertUnauthorized();
    $this->getJson('/api/v1/status', ['Authorization' => 'Bearer wrong'])->assertUnauthorized();

    Setting::forget(Settings::API_TOKEN_HASH);
    $this->getJson('/api/v1/status', api())->assertUnauthorized();
});

it('accepts the X-Api-Key header', function () {
    $this->getJson('/api/v1/status', ['X-Api-Key' => $this->token])->assertOk();
});

it('reports processing status and execution counts', function () {
    Execution::factory()->count(2)->create(['status' => ExecutionStatus::QUEUED]);
    Execution::factory()->create(['status' => ExecutionStatus::FAILED]);

    $this->getJson('/api/v1/status', api())
        ->assertOk()
        ->assertJsonPath('processing.paused', false)
        ->assertJsonPath('executions.queued', 2)
        ->assertJsonPath('executions.failed', 1)
        ->assertJsonCount(3, 'workers');
});

it('pauses and resumes processing', function () {
    $this->postJson('/api/v1/processing/pause', [], api())
        ->assertOk()
        ->assertJsonPath('processing.reasons', ['manual']);

    $this->postJson('/api/v1/processing/resume', [], api())
        ->assertJsonPath('processing.paused', false);
});

it('lists and shows libraries', function () {
    $library = Library::factory()->create(['base_path' => '/media/tv']);

    $this->getJson('/api/v1/libraries', api())
        ->assertOk()
        ->assertJsonPath('data.0.base_path', '/media/tv');

    $this->getJson("/api/v1/libraries/{$library->id}", api())
        ->assertOk()
        ->assertJsonPath('data.id', $library->id);
});

it('triggers a library scan', function () {
    $library = Library::factory()->create();

    $this->postJson("/api/v1/libraries/{$library->id}/scan", [], api())
        ->assertAccepted()
        ->assertJsonPath('data.status', 'pending_scan');

    Queue::assertPushed(ScanLibrary::class);
});

it('lists executions filtered by status', function () {
    Execution::factory()->create(['status' => ExecutionStatus::FAILED]);
    Execution::factory()->create(['status' => ExecutionStatus::COMPLETED]);

    $this->getJson('/api/v1/executions?status=failed', api())
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', 'failed')
        ->assertJsonMissingPath('data.0.output');
});

it('shows an execution with its output', function () {
    $execution = Execution::factory()->create(['output' => 'log line']);

    $this->getJson("/api/v1/executions/{$execution->id}", api())
        ->assertOk()
        ->assertJsonPath('data.output', 'log line');
});

it('controls executions', function () {
    $execution = Execution::factory()->create(['status' => ExecutionStatus::PROCESSING]);

    $this->postJson("/api/v1/executions/{$execution->id}/pause", [], api())->assertOk()->assertJsonPath('data.status', 'paused');
    $this->postJson("/api/v1/executions/{$execution->id}/resume", [], api())->assertOk()->assertJsonPath('data.status', 'processing');
    $this->postJson("/api/v1/executions/{$execution->id}/stop", [], api())->assertOk()->assertJsonPath('data.status', 'stopped');
    $this->postJson("/api/v1/executions/{$execution->id}/retry", [], api())->assertOk()->assertJsonPath('data.status', 'queued');
});

it('returns a conflict for actions that do not apply', function () {
    $execution = Execution::factory()->create(['status' => ExecutionStatus::COMPLETED]);

    $this->postJson("/api/v1/executions/{$execution->id}/pause", [], api())->assertConflict();
});
