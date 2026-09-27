<?php

use App\Jobs\OrchestrateWorkers;
use App\LibraryJobId;
use App\Models\Worker;
use App\Services\SupervisorService;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Queue;

it('scales each supervisor program to the worker concurrency', function () {
    Queue::fake();
    Worker::where('job_type', LibraryJobId::TRANSCODE_MEDIA)->update(['concurrency' => 3]);

    $supervisor = Mockery::mock(SupervisorService::class);
    foreach (range(0, 2) as $i) {
        $supervisor->shouldReceive('startWorker')->with('Transcoder', $i)->once()->andReturnTrue();
    }
    foreach (range(3, Worker::MAX_CONCURRENCY - 1) as $i) {
        $supervisor->shouldReceive('stopWorker')->with('Transcoder', $i)->once()->andReturnTrue();
    }

    (new OrchestrateWorkers(LibraryJobId::TRANSCODE_MEDIA))->handle($supervisor, app(Logger::class));
});

it('stops every process of a disabled or deleted worker', function (callable $change) {
    Queue::fake();
    $worker = Worker::where('job_type', LibraryJobId::CONVERT_SUBTITLE)->firstOrFail();
    $change($worker);

    $supervisor = Mockery::mock(SupervisorService::class);
    $supervisor->shouldNotReceive('startWorker');
    $supervisor->shouldReceive('stopWorker')->times(Worker::MAX_CONCURRENCY)->andReturnTrue();

    (new OrchestrateWorkers(LibraryJobId::CONVERT_SUBTITLE))->handle($supervisor, app(Logger::class));
})->with([
    'disabled' => [fn (Worker $w) => $w->update(['enabled' => false])],
    'deleted' => [fn (Worker $w) => $w->delete()],
]);

it('re-orchestrates when a worker configuration changes', function () {
    Queue::fake();
    $worker = Worker::where('job_type', LibraryJobId::TRANSCODE_MEDIA)->firstOrFail();

    $worker->update(['concurrency' => 2]);
    $worker->update(['name' => 'Renamed']);

    Queue::assertPushed(OrchestrateWorkers::class, 1);
});

it('parses running process counts from supervisorctl status', function () {
    $status = <<<'TXT'
    ConvertSubs:ConvertSubs_00       STOPPED   Not started
    Transcoder:Transcoder_00         RUNNING   pid 101, uptime 0:10:00
    Transcoder:Transcoder_01         RUNNING   pid 102, uptime 0:10:00
    Transcoder:Transcoder_02         STOPPED   Sep 27 10:00 AM
    nginx                            RUNNING   pid 12, uptime 1:00:00
    TXT;

    expect(SupervisorService::parseRunningCounts($status))->toBe([
        'ConvertSubs' => 0,
        'Transcoder' => 2,
        'nginx' => 1,
    ]);
});
