<?php

use App\ExecutionStatus;
use App\Jobs\TranscodeMediaJob;
use App\Models\Execution;
use App\Models\Library;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

beforeEach(function () {
    $this->tempDir = storage_path('app/testing_media_'.uniqid());
    if (! File::exists($this->tempDir)) {
        File::makeDirectory($this->tempDir, 0777, true, true);
    }

    $this->sourceFile = $this->tempDir.'/test.mkv';
    $this->outputPath = $this->tempDir.'/test_HEVC.mkv';

    // Generate a simple dummy video file using ffmpeg
    $ffmpeg = config('services.ffmpeg.bin', 'ffmpeg');

    // Create a simple black video compatible with the zscale filter chain (gbrpf32le)
    $command = "{$ffmpeg} -f lavfi -i color=c=black:s=320x240:d=1 -t 1 -c:v libx264 -pix_fmt gbrpf32le {$this->sourceFile}";
    exec($command);

    if (! File::exists($this->sourceFile)) {
        throw new Exception("Failed to create source media file at {$this->sourceFile}");
    }
});

afterEach(function () {
    Cache::forget('media_processing_paused');
    Cache::forget('active_streams');

    if (File::exists($this->tempDir)) {
        File::deleteDirectory($this->tempDir);
    }
});

it('successfully transcodes the generated mkv file using a mock process', function () {
    $processMock = Mockery::mock(Process::class);
    $processMock->shouldReceive('setTimeout')->andReturnSelf();
    $processMock->shouldReceive('isStarted')->andReturn(true);
    $processMock->shouldReceive('isRunning')->andReturn(false);
    $processMock->shouldReceive('isSuccessful')->andReturn(true);
    $processMock->shouldReceive('getErrorOutput')->andReturn('');

    $job = new TranscodeMediaJob($this->sourceFile, false, $processMock);
    $job->handle();

    expect(true)->toBeTrue();
});

it('pauses when media_processing_paused is set', function () {
    Cache::put('media_processing_paused', true);

    $shouldPause = (fn () => $this->shouldPause())->bindTo(new TranscodeMediaJob($this->sourceFile), TranscodeMediaJob::class);

    expect($shouldPause())->toBeTrue();
})->group('pause');

it('pauses when active_streams is greater than zero', function () {
    Cache::put('active_streams', 2);

    $shouldPause = (fn () => $this->shouldPause())->bindTo(new TranscodeMediaJob($this->sourceFile), TranscodeMediaJob::class);

    expect($shouldPause())->toBeTrue();
})->group('pause');

it('does not pause when no conditions are set', function () {
    $shouldPause = (fn () => $this->shouldPause())->bindTo(new TranscodeMediaJob($this->sourceFile), TranscodeMediaJob::class);

    expect($shouldPause())->toBeFalse();
})->group('pause');

it('transcodes using real ffmpeg with software codec for testing compatibility', function () {
    config([
        'services.ffmpeg.use_nvenc' => false,
        'services.ffmpeg.video_filter' => 'format=yuv420p',
    ]);

    $job = new TranscodeMediaJob($this->sourceFile);

    try {
        $job->handle();
    } catch (RuntimeException $e) {
        if (str_contains($e->getMessage(), 'Unknown encoder')) {
            $this->markTestSkipped('Environment does not support libx265 (software encoding).');
        }
        throw $e;
    }

    expect(File::exists($this->outputPath))->toBeTrue();

    $probeOutput = [];
    exec("ffprobe -v quiet -print_format json -show_streams {$this->outputPath} 2>&1", $probeOutput);
    $probe = json_decode(implode("\n", $probeOutput), true);

    expect($probe['streams'][0]['codec_name'])->toBe('hevc');
    expect($probe['streams'][0]['width'])->toBe(320);
    expect($probe['streams'][0]['height'])->toBe(240);
});

it('updates execution status when executionId is provided', function () {
    $library = Library::factory()->create();
    $libraryJob = $library->libraryJobs()->create(['job_id' => 'transcode_media']);
    $execution = Execution::factory()->create([
        'library_job_id' => $libraryJob->id,
        'file_path' => $this->sourceFile,
        'status' => ExecutionStatus::QUEUED,
    ]);

    $processMock = Mockery::mock(Process::class);
    $processMock->shouldReceive('setTimeout')->andReturnSelf();
    $processMock->shouldReceive('isStarted')->andReturn(true);
    $processMock->shouldReceive('isRunning')->andReturn(false);
    $processMock->shouldReceive('isSuccessful')->andReturn(true);
    $processMock->shouldReceive('getErrorOutput')->andReturn('');

    $job = new TranscodeMediaJob($this->sourceFile, false, $processMock);
    $job->setExecutionId($execution->id);
    $job->handle();

    $execution->refresh();
    expect($execution->status)->toBe(ExecutionStatus::COMPLETED);
    expect($execution->started_at)->not->toBeNull();
    expect($execution->finished_at)->not->toBeNull();
});
