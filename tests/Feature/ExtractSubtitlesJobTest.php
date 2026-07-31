<?php

use App\Jobs\ExtractSubtitles;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

beforeEach(function () {
    $this->tempDir = storage_path('app/testing_subtitles_'.uniqid());
    if (! File::exists($this->tempDir)) {
        File::makeDirectory($this->tempDir, 0777, true, true);
    }

    $this->videoFile = $this->tempDir.'/test.mkv';
    $this->subtitleFile = $this->tempDir.'/test.en.srt';

    $ffmpeg = config('services.ffmpeg.bin', 'ffmpeg');
    exec("{$ffmpeg} -y -f lavfi -i color=c=black:s=320x240:d=1 -t 1 -c:v libx264 -pix_fmt yuv420p {$this->videoFile} 2>&1 >/dev/null");

    $srtContent = "1\n00:00:01,000 --> 00:00:04,000\nEmbedded Test Subtitle\n\n";
    File::put($this->tempDir.'/subtitles.srt', $srtContent);

    $tmpVideo = $this->tempDir.'/test_tmp.mkv';
    exec("{$ffmpeg} -y -i {$this->videoFile} -i {$this->tempDir}/subtitles.srt -c:v copy -c:a copy -c:s srt -metadata:s:s:0 language=eng {$tmpVideo} 2>&1 >/dev/null");
    File::move($tmpVideo, $this->videoFile);
});

afterEach(function () {
    if (File::exists($this->tempDir)) {
        File::deleteDirectory($this->tempDir);
    }
});

it('successfully extracts subtitles using a mock process', function () {
    $probeOutput = json_encode(['streams' => [
        ['index' => 1, 'codec_name' => 'srt', 'tags' => ['language' => 'en']],
    ]]);

    $mock = Mockery::mock(Process::class);
    $mock->shouldReceive('run')->once();
    $mock->shouldReceive('start')->times(2);
    $mock->shouldReceive('setTimeout')->with(null)->times(2);
    $mock->shouldReceive('isRunning')->times(4)->andReturn(true, false, true, false);
    $mock->shouldReceive('checkTimeout')->times(2);
    $mock->shouldReceive('isSuccessful')->times(3)->andReturn(true);
    $mock->shouldReceive('getErrorOutput')->andReturn('');
    $mock->shouldReceive('getOutput')->andReturn($probeOutput);

    $factory = fn (array $command) => $mock;

    $job = new ExtractSubtitles($this->videoFile, true, $factory);
    $job->handle();

    expect(true)->toBeTrue();
});

it('extracts embedded subtitles to sidecar files', function () {
    $mkvmerge = config('services.mkvmerge.bin', 'mkvmerge');
    if (! File::exists($mkvmerge) && ! File::exists('/usr/bin/mkvmerge') && ! File::exists('/usr/local/bin/mkvmerge')) {
        $this->markTestSkipped('mkvmerge is not available.');
    }

    $job = new ExtractSubtitles($this->videoFile);
    $job->handle();

    expect($this->subtitleFile)->toBeFile();

    $outputContent = File::get($this->subtitleFile);
    expect($outputContent)
        ->toContain('Embedded Test Subtitle')
        ->toContain('00:00:01,000 --> 00:00:04,000');
});

it('strips internal subtitle tracks from mkv after extraction', function () {
    $mkvmerge = config('services.mkvmerge.bin', 'mkvmerge');
    if (! File::exists($mkvmerge) && ! File::exists('/usr/bin/mkvmerge') && ! File::exists('/usr/local/bin/mkvmerge')) {
        $this->markTestSkipped('mkvmerge is not available.');
    }

    $probeBefore = new Process(['ffprobe', '-v', 'error', '-select_streams', 's', '-show_entries', 'stream=codec_name', '-of', 'json', $this->videoFile]);
    $probeBefore->run();
    $streamsBefore = json_decode($probeBefore->getOutput(), true);
    $hasSubBefore = collect($streamsBefore['streams'] ?? [])->contains(fn ($s) => str_contains($s['codec_name'] ?? '', 'sub'));

    $job = new ExtractSubtitles($this->videoFile, true);
    $job->handle();

    $probeAfter = new Process(['ffprobe', '-v', 'error', '-select_streams', 's', '-show_entries', 'stream=codec_name', '-of', 'json', $this->videoFile]);
    $probeAfter->run();
    $streamsAfter = json_decode($probeAfter->getOutput(), true);
    $hasSubAfter = collect($streamsAfter['streams'] ?? [])->contains(fn ($s) => str_contains($s['codec_name'] ?? '', 'sub'));

    expect($hasSubBefore)->toBeTrue();
    expect($hasSubAfter)->toBeFalse();
});
