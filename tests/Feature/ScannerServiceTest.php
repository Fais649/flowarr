<?php

use App\ExecutionStatus;
use App\Jobs\ConvertSubtitle;
use App\Jobs\ExtractSubtitles;
use App\Jobs\TranscodeMedia;
use App\LibraryJobId;
use App\Models\Execution;
use App\Models\Library;
use App\Models\Worker;
use App\Services\ScannerService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();

    $this->mediaDir = storage_path('app/testing_scanner_'.uniqid());
    File::makeDirectory($this->mediaDir, 0777, true, true);

    $this->library = Library::factory()->create(['base_path' => $this->mediaDir]);
    $this->library->workers()->attach(Worker::pluck('id'));
});

afterEach(function () {
    File::deleteDirectory($this->mediaDir);
});

function h264(string $path, bool $withSubtitles = false): void
{
    File::ensureDirectoryExists(dirname($path));
    $srt = dirname($path).'/in.txt';
    File::put($srt, "1\n00:00:00,100 --> 00:00:00,500\nHi\n\n");
    $subs = $withSubtitles ? '-f srt -i '.escapeshellarg($srt).' -map 0 -map 1 -c:s srt' : '';
    exec('ffmpeg -loglevel error -y -f lavfi -i testsrc=s=64x64:d=1 '.$subs.' -c:v libx264 -pix_fmt yuv420p '.escapeshellarg($path));
    File::delete($srt);
}

function scan(Library $library): int
{
    return app(ScannerService::class)->scan($library);
}

function executionsFor(LibraryJobId $jobId)
{
    return Execution::whereHas('libraryJob', fn ($q) => $q->where('job_id', $jobId))->get();
}

it('queues executions with the worker and a file fingerprint', function () {
    $file = $this->mediaDir.'/Some.Movie.2020.1080p.mkv';
    h264($file);

    expect(scan($this->library))->toBe(1);

    $execution = executionsFor(LibraryJobId::TRANSCODE_MEDIA)->sole();
    expect($execution->file_path)->toBe($file)
        ->and($execution->status)->toBe(ExecutionStatus::QUEUED)
        ->and($execution->worker_id)->toBe(Worker::where('job_type', LibraryJobId::TRANSCODE_MEDIA)->value('id'))
        ->and($execution->file_size)->toBe(filesize($file))
        ->and($execution->file_mtime)->toBe(filemtime($file));

    Queue::assertPushed(TranscodeMedia::class, 1);
});

it('ignores hidden files, resource forks, temp files and hidden directories', function () {
    h264($this->mediaDir.'/._movie.mkv');
    h264($this->mediaDir.'/.hidden.mkv');
    h264($this->mediaDir.'/movie.tmp.mkv');
    h264($this->mediaDir.'/.Trash/movie.mkv');
    h264($this->mediaDir.'/@eaDir/movie.mkv');
    File::put($this->mediaDir.'/types.d.ts', 'export {}');

    expect(scan($this->library))->toBe(0);
});

it('only queues subtitle extraction for videos with text subtitles', function () {
    h264($this->mediaDir.'/with-subs.mkv', withSubtitles: true);
    h264($this->mediaDir.'/without-subs.mkv');

    scan($this->library);

    expect(executionsFor(LibraryJobId::EXTRACT_SUBTITLES)->pluck('file_path')->all())
        ->toBe([$this->mediaDir.'/with-subs.mkv']);
    Queue::assertPushed(ExtractSubtitles::class, 1);
});

it('queues conversion for ass and vtt files unless an srt already exists', function () {
    File::put($this->mediaDir.'/a.ass', '[Script Info]');
    File::put($this->mediaDir.'/b.vtt', 'WEBVTT');
    File::put($this->mediaDir.'/c.vtt', 'WEBVTT');
    File::put($this->mediaDir.'/c.srt', '1');
    File::put($this->mediaDir.'/d.sup', 'image subs cannot be converted');

    scan($this->library);

    expect(executionsFor(LibraryJobId::CONVERT_SUBTITLE)->pluck('file_path')->sort()->values()->all())
        ->toBe([$this->mediaDir.'/a.ass', $this->mediaDir.'/b.vtt']);
    Queue::assertPushed(ConvertSubtitle::class, 2);
});

it('does not queue hevc videos for transcoding', function () {
    exec('ffmpeg -loglevel error -y -f lavfi -i testsrc=s=64x64:d=1 -c:v libx265 '.escapeshellarg($this->mediaDir.'/done.mkv'));

    scan($this->library);

    expect(executionsFor(LibraryJobId::TRANSCODE_MEDIA))->toHaveCount(0);
});

it('skips disabled workers and libraries without workers', function () {
    h264($this->mediaDir.'/movie.mkv');
    Worker::query()->update(['enabled' => false]);

    expect(scan($this->library))->toBe(0);

    Worker::query()->update(['enabled' => true]);
    $this->library->workers()->detach();

    expect(scan($this->library))->toBe(0);
});

it('does not re-queue failed or completed files that did not change', function (ExecutionStatus $status) {
    $file = $this->mediaDir.'/movie.mkv';
    h264($file);
    scan($this->library);
    executionsFor(LibraryJobId::TRANSCODE_MEDIA)->sole()->update(['status' => $status]);

    expect(scan($this->library))->toBe(0);
})->with([ExecutionStatus::FAILED, ExecutionStatus::COMPLETED, ExecutionStatus::STOPPED, ExecutionStatus::PROCESSING]);

it('re-evaluates a file that changed since its last execution', function () {
    $file = $this->mediaDir.'/movie.mkv';
    h264($file);
    scan($this->library);
    executionsFor(LibraryJobId::TRANSCODE_MEDIA)->sole()->update(['status' => ExecutionStatus::FAILED]);

    // e.g. the file was replaced by a new download
    touch($file, time() + 60);
    clearstatcache();

    expect(scan($this->library))->toBe(1)
        ->and(executionsFor(LibraryJobId::TRANSCODE_MEDIA))->toHaveCount(2);
});
