<?php

use App\ExecutionStatus;
use App\Jobs\TranscodeMedia;
use App\LibraryJobId;
use App\Models\Execution;
use App\Models\Library;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
    $this->actingAs(User::factory()->create());
    $this->dir = storage_path('app/manual_'.uniqid());
    File::makeDirectory($this->dir.'/season', 0777, true);
    File::put($this->dir.'/episode.mkv', 'test video');
    File::put($this->dir.'/season/episode2.mkv', 'test video');
    File::put($this->dir.'/other.txt', 'not media');
    $this->library = Library::factory()->create(['base_path' => $this->dir]);
    $this->worker = Worker::where('job_type', LibraryJobId::TRANSCODE_MEDIA)->firstOrFail();
    $this->worker->update(['enabled' => true]);
    $this->library->workers()->attach($this->worker);
    $this->payload = ['library_id' => $this->library->id, 'job_id' => 'transcode_media', 'files' => [$this->dir.'/episode.mkv'], 'mode' => 'enqueue'];
});

afterEach(function () {
    File::deleteDirectory($this->dir);
});

it('browses immediate folders and media files inside the library', function () {
    $this->getJson('/executions/files/'.$this->library->id)->assertOk()
        ->assertJsonCount(2, 'entries')->assertJsonPath('entries.0.name', 'season')->assertJsonPath('parent', null);
    $this->getJson('/executions/files/'.$this->library->id.'?path='.urlencode($this->dir.'/season'))->assertOk()->assertJsonPath('entries.0.name', 'episode2.mkv');
});

it('enqueues selected files with their fingerprint', function () {
    $this->post('/executions/manual', $this->payload)->assertRedirect('/executions');
    $execution = Execution::firstOrFail();
    expect($execution->file_path)->toBe($this->dir.'/episode.mkv')->and($execution->file_size)->toBe(10)->and($execution->status)->toBe(ExecutionStatus::QUEUED);
    Queue::assertPushed(TranscodeMedia::class);
});

it('puts run now requests on the priority queue', function () {
    $this->post('/executions/manual', [...$this->payload, 'mode' => 'now'])->assertRedirect();
    Queue::assertPushedOn('transcode-media-now', TranscodeMedia::class);
});

it('skips files already active or awaiting replacement', function (string $status, ?string $replacement) {
    Execution::factory()->create(['file_path' => $this->dir.'/episode.mkv', 'status' => $status, 'replacement_status' => $replacement]);
    $this->post('/executions/manual', $this->payload)->assertRedirect();
    Queue::assertNotPushed(TranscodeMedia::class);
    expect(Execution::count())->toBe(1);
})->with([['queued', null], ['completed', 'pending']]);

it('rejects external paths and unsupported operations before enqueuing any files', function () {
    $this->postJson('/executions/manual', [...$this->payload, 'files' => [$this->dir.'/episode.mkv', '/etc/passwd']])->assertUnprocessable();
    $this->postJson('/executions/manual', [...$this->payload, 'job_id' => 'convert_sub'])->assertUnprocessable();
    $this->getJson('/executions/files/'.$this->library->id.'?path=/etc')->assertUnprocessable();
    Queue::assertNotPushed(TranscodeMedia::class);
});

it('rejects disabled workers and symlink escapes', function () {
    symlink('/etc/passwd', $this->dir.'/escape.mkv');
    $this->postJson('/executions/manual', [...$this->payload, 'files' => [$this->dir.'/escape.mkv']])->assertUnprocessable();
    $this->worker->update(['enabled' => false]);
    $this->postJson('/executions/manual', $this->payload)->assertUnprocessable();
    Queue::assertNotPushed(TranscodeMedia::class);
});
