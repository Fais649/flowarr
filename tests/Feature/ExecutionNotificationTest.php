<?php

use App\ExecutionStatus;
use App\Jobs\SendExecutionNotification;
use App\LibraryJobId;
use App\Models\Execution;
use App\Models\LibraryJob;
use App\Models\Setting;
use App\Models\User;
use App\Services\ExecutionNotifier;
use App\Settings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Setting::set(Settings::NOTIFICATION_WEBHOOK_URL, 'https://hooks.example.test/flowarr');
});

function finishedExecution(ExecutionStatus $status): Execution
{
    return Execution::factory()->create([
        'library_job_id' => LibraryJob::factory()->create(['job_id' => LibraryJobId::TRANSCODE_MEDIA])->id,
        'file_path' => '/media/Movies/Alien.mkv',
        'status' => $status,
        'message' => $status === ExecutionStatus::FAILED ? 'Unknown encoder' : null,
        'started_at' => now()->subMinutes(5),
        'finished_at' => now(),
    ]);
}

it('posts a chat-friendly payload for failed executions', function () {
    Http::fake();

    SendExecutionNotification::dispatchSync(finishedExecution(ExecutionStatus::FAILED)->id);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://hooks.example.test/flowarr'
            && $request['event'] === 'execution.failed'
            && $request['content'] === '❌ Transcode Media failed: Alien.mkv — Unknown encoder'
            && $request['text'] === $request['content']
            && $request['execution']['job_type'] === 'transcode_media'
            && $request['execution']['duration_seconds'] === 300;
    });
});

it('only notifies for the enabled outcomes', function () {
    Queue::fake();
    $notifier = app(ExecutionNotifier::class);

    $notifier->executionFinished(finishedExecution(ExecutionStatus::COMPLETED));
    $notifier->executionFinished(finishedExecution(ExecutionStatus::FAILED));
    Queue::assertPushed(SendExecutionNotification::class, 1);

    Setting::set(Settings::NOTIFY_ON_COMPLETED, '1');
    $notifier->executionFinished(finishedExecution(ExecutionStatus::COMPLETED));
    Queue::assertPushed(SendExecutionNotification::class, 2);

    $notifier->executionFinished(finishedExecution(ExecutionStatus::STOPPED));
    Queue::assertPushed(SendExecutionNotification::class, 2);
});

it('does not notify without a webhook url', function () {
    Queue::fake();
    Setting::forget(Settings::NOTIFICATION_WEBHOOK_URL);

    app(ExecutionNotifier::class)->executionFinished(finishedExecution(ExecutionStatus::FAILED));

    Queue::assertNothingPushed();
});

it('sends a test notification from the settings page', function () {
    Http::fake(['hooks.example.test/*' => Http::response(status: 204)]);
    $this->actingAs(User::factory()->create());

    $this->post('/config/processing/test-notification')->assertRedirect();

    Http::assertSent(fn ($request) => $request['event'] === 'test');
});

it('reports a failing test notification', function () {
    Http::fake(['hooks.example.test/*' => Http::response('nope', 500)]);
    $this->actingAs(User::factory()->create());

    $this->post('/config/processing/test-notification')
        ->assertRedirect()
        ->assertInertiaFlash('toast.type', 'error');
});
