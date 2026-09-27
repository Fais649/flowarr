<?php

use App\ExecutionStatus;
use App\Jobs\ConvertSubtitle;
use App\Jobs\ExtractSubtitles;
use App\Jobs\TranscodeMedia;
use App\LibraryJobId;
use App\Models\Execution;
use App\Models\Library;
use App\Models\Worker;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

/**
 * End-to-end tests for the media jobs: each job runs its real shell script
 * against small files generated with ffmpeg.
 */
beforeEach(function () {
    config(['services.ffmpeg.enable_gpu_transcoding' => false]);

    $this->mediaDir = storage_path('app/testing_media_'.uniqid());
    File::makeDirectory($this->mediaDir, 0777, true, true);

    $this->library = Library::factory()->create(['base_path' => $this->mediaDir]);
});

afterEach(function () {
    File::deleteDirectory($this->mediaDir);
});

function makeVideo(string $path, bool $withSubtitles = false, string $codec = 'libx264'): void
{
    $srt = dirname($path).'/source.srt';
    File::put($srt, "1\n00:00:00,100 --> 00:00:00,900\nEmbedded Test Subtitle\n\n");

    $inputs = $withSubtitles ? "-i {$srt} -map 0:v -map 1 -c:s ".(str_ends_with($path, '.mp4') ? 'mov_text' : 'srt').' -metadata:s:s:0 language=eng' : '';
    exec("ffmpeg -loglevel error -y -f lavfi -i testsrc=s=160x120:d=1 {$inputs} -c:v {$codec} -pix_fmt yuv420p ".escapeshellarg($path));
    File::delete($srt);

    if (! File::exists($path)) {
        throw new RuntimeException("Failed to create {$path}");
    }
}

function probeStreams(string $path): array
{
    $process = new Process(['ffprobe', '-v', 'error', '-show_entries', 'stream=codec_type,codec_name', '-of', 'json', $path]);
    $process->mustRun();

    return json_decode($process->getOutput(), true)['streams'] ?? [];
}

function executionFor(Library $library, LibraryJobId $jobId, string $path, bool $replaceOriginal = false): Execution
{
    $worker = Worker::where('job_type', $jobId)->firstOrFail();
    $worker->update(['replace_original' => $replaceOriginal]);
    $library->workers()->syncWithoutDetaching([$worker->id]);

    return Execution::factory()->create([
        'library_job_id' => $library->libraryJobs()->firstOrCreate(['job_id' => $jobId])->id,
        'worker_id' => $worker->id,
        'file_path' => $path,
        'status' => ExecutionStatus::QUEUED,
        'started_at' => null,
        'finished_at' => null,
    ]);
}

it('transcodes a video to a sibling _hevc.mkv file', function () {
    $source = $this->mediaDir.'/Some.Movie.2020.mkv';
    makeVideo($source, withSubtitles: true);
    $execution = executionFor($this->library, LibraryJobId::TRANSCODE_MEDIA, $source);

    TranscodeMedia::dispatchSync($execution);

    $execution->refresh();
    expect($execution->status)->toBe(ExecutionStatus::COMPLETED)
        ->and($execution->progress)->toBe(100.0)
        ->and($execution->started_at)->not->toBeNull()
        ->and($execution->finished_at)->not->toBeNull()
        ->and($execution->output)->toContain('libx265');

    $output = $this->mediaDir.'/Some.Movie.2020_hevc.mkv';
    expect($source)->toBeFile()->and($output)->toBeFile();

    $streams = collect(probeStreams($output));
    expect($streams->firstWhere('codec_type', 'video')['codec_name'])->toBe('hevc')
        ->and($streams->where('codec_type', 'subtitle'))->toHaveCount(1);
});

it('replaces an mp4 original with an mkv when replace_original is enabled', function () {
    $source = $this->mediaDir.'/clip.mp4';
    makeVideo($source, withSubtitles: true);
    $execution = executionFor($this->library, LibraryJobId::TRANSCODE_MEDIA, $source, replaceOriginal: true);

    TranscodeMedia::dispatchSync($execution);

    expect($execution->refresh()->status)->toBe(ExecutionStatus::COMPLETED)
        ->and($source)->not->toBeFile()
        ->and($this->mediaDir.'/clip.mkv')->toBeFile();

    expect(collect(probeStreams($this->mediaDir.'/clip.mkv'))->pluck('codec_name')->all())
        ->toContain('hevc', 'subrip');
});

it('extracts embedded subtitles to sidecar files', function () {
    $source = $this->mediaDir.'/show.mkv';
    makeVideo($source, withSubtitles: true, codec: 'libx265');
    $execution = executionFor($this->library, LibraryJobId::EXTRACT_SUBTITLES, $source);

    ExtractSubtitles::dispatchSync($execution);

    expect($execution->refresh()->status)->toBe(ExecutionStatus::COMPLETED);
    expect(File::get($this->mediaDir.'/show.en.srt'))->toContain('Embedded Test Subtitle');
    expect(collect(probeStreams($source))->where('codec_type', 'subtitle'))->toHaveCount(1);
});

it('names sidecars after the future transcode output', function () {
    $source = $this->mediaDir.'/show.mkv';
    makeVideo($source, withSubtitles: true);
    $transcoder = Worker::where('job_type', LibraryJobId::TRANSCODE_MEDIA)->firstOrFail();
    $this->library->workers()->attach($transcoder);
    $execution = executionFor($this->library, LibraryJobId::EXTRACT_SUBTITLES, $source);

    ExtractSubtitles::dispatchSync($execution);

    expect($this->mediaDir.'/show_hevc.en.srt')->toBeFile();
});

it('strips extracted subtitle streams when replace_original is enabled', function () {
    $source = $this->mediaDir.'/show.mkv';
    makeVideo($source, withSubtitles: true, codec: 'libx265');
    $execution = executionFor($this->library, LibraryJobId::EXTRACT_SUBTITLES, $source, replaceOriginal: true);

    ExtractSubtitles::dispatchSync($execution);

    expect($execution->refresh()->status)->toBe(ExecutionStatus::COMPLETED)
        ->and($this->mediaDir.'/show.en.srt')->toBeFile();

    $streams = collect(probeStreams($source));
    expect($streams->where('codec_type', 'subtitle'))->toHaveCount(0)
        ->and($streams->where('codec_type', 'video'))->toHaveCount(1);
});

it('converts a vtt subtitle to srt', function () {
    $source = $this->mediaDir.'/episode.en.vtt';
    File::put($source, "WEBVTT\n\n00:00:01.000 --> 00:00:04.000\nHello from VTT\n");
    $execution = executionFor($this->library, LibraryJobId::CONVERT_SUBTITLE, $source);

    ConvertSubtitle::dispatchSync($execution);

    expect($execution->refresh()->status)->toBe(ExecutionStatus::COMPLETED)
        ->and($source)->toBeFile()
        ->and(File::get($this->mediaDir.'/episode.en.srt'))->toContain('00:00:01,000 --> 00:00:04,000');
});

it('deletes the source subtitle after conversion when replace_original is enabled', function () {
    $srt = $this->mediaDir.'/input.txt';
    File::put($srt, "1\n00:00:01,000 --> 00:00:02,000\nStyled line\n\n");
    $source = $this->mediaDir.'/episode.ass';
    exec('ffmpeg -loglevel error -y -f srt -i '.escapeshellarg($srt).' '.escapeshellarg($source));
    $execution = executionFor($this->library, LibraryJobId::CONVERT_SUBTITLE, $source, replaceOriginal: true);

    ConvertSubtitle::dispatchSync($execution);

    expect($execution->refresh()->status)->toBe(ExecutionStatus::COMPLETED)
        ->and($source)->not->toBeFile()
        ->and(File::get($this->mediaDir.'/episode.srt'))->toContain('Styled line');
});

it('refuses to overwrite an existing srt when converting', function () {
    $source = $this->mediaDir.'/episode.vtt';
    File::put($source, "WEBVTT\n\n00:00:01.000 --> 00:00:04.000\nHello\n");
    File::put($this->mediaDir.'/episode.srt', 'keep me');
    $execution = executionFor($this->library, LibraryJobId::CONVERT_SUBTITLE, $source);

    ConvertSubtitle::dispatchSync($execution);

    $execution->refresh();
    expect($execution->status)->toBe(ExecutionStatus::FAILED)
        ->and($execution->message)->toContain('Refusing to overwrite')
        ->and(File::get($this->mediaDir.'/episode.srt'))->toBe('keep me');
});

it('fails with a clear message when the file is missing', function () {
    $execution = executionFor($this->library, LibraryJobId::TRANSCODE_MEDIA, $this->mediaDir.'/gone.mkv');

    TranscodeMedia::dispatchSync($execution);

    $execution->refresh();
    expect($execution->status)->toBe(ExecutionStatus::FAILED)
        ->and($execution->message)->toBe("File not found: {$this->mediaDir}/gone.mkv");
});

it('does not run executions that were stopped while queued', function () {
    $source = $this->mediaDir.'/movie.mkv';
    makeVideo($source);
    $execution = executionFor($this->library, LibraryJobId::TRANSCODE_MEDIA, $source);
    $job = new TranscodeMedia($execution);
    $execution->update(['status' => ExecutionStatus::STOPPED]);

    $job->handle();

    expect($execution->refresh()->status)->toBe(ExecutionStatus::STOPPED)
        ->and($this->mediaDir.'/movie_hevc.mkv')->not->toBeFile();
});

it('does nothing when the execution was deleted while queued', function () {
    $execution = executionFor($this->library, LibraryJobId::TRANSCODE_MEDIA, $this->mediaDir.'/movie.mkv');
    $job = new TranscodeMedia($execution);
    $execution->delete();

    $job->handle();

    expect(Execution::count())->toBe(0);
});

it('marks the execution failed when the job itself fails', function () {
    $execution = executionFor($this->library, LibraryJobId::TRANSCODE_MEDIA, $this->mediaDir.'/movie.mkv');
    $execution->update(['status' => ExecutionStatus::PROCESSING]);

    (new TranscodeMedia($execution))->failed(new RuntimeException('Worker crashed'));

    $execution->refresh();
    expect($execution->status)->toBe(ExecutionStatus::FAILED)
        ->and($execution->message)->toBe('Worker crashed')
        ->and($execution->finished_at)->not->toBeNull();
});

it('never lets the queue worker time out long media jobs', function () {
    $job = new TranscodeMedia(Execution::factory()->create());

    expect($job->timeout)->toBe(0)->and($job->tries)->toBe(1);
});
