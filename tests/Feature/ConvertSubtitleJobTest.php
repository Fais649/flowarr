<?php

use App\Jobs\ConvertSubtitleJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->tempDir = storage_path('app/testing_subtitles_'.uniqid());
    File::makeDirectory($this->tempDir, 0777, true, true);
    Config::set('services.ffmpeg.bin', '/usr/bin/ffmpeg');
});

afterEach(function () {
    Cache::forget('media_processing_paused');

    File::deleteDirectory($this->tempDir);
});

it('converts vtt to srt successfully', function () {
    $sourceFile = $this->tempDir.'/sample.vtt';
    $expectedFile = $this->tempDir.'/sample.srt';

    $vttContent = "WEBVTT\n\n1\n00:00:01.000 --> 00:00:04.000\nTest Subtitle\n\n";
    File::put($sourceFile, $vttContent);

    $job = new ConvertSubtitleJob($sourceFile);
    $job->handle();

    expect($expectedFile)->toBeFile();

    $outputContent = File::get($expectedFile);
    expect($outputContent)
        ->toContain('Test Subtitle')
        ->toContain('00:00:01,000 --> 00:00:04,000');
});

it('preserves source file when conversion fails', function () {
    $sourceFile = $this->tempDir.'/corrupt.bin';

    File::put($sourceFile, random_bytes(100));

    $job = new ConvertSubtitleJob($sourceFile);

    expect(fn () => $job->handle())->toThrow(Exception::class);
    expect($sourceFile)->toBeFile();
});

it('pauses when media_processing_paused is set', function () {
    Cache::put('media_processing_paused', true);

    $shouldPause = (fn () => $this->shouldPause())->bindTo(new ConvertSubtitleJob($this->tempDir.'/test.vtt'), ConvertSubtitleJob::class);

    expect($shouldPause())->toBeTrue();
})->group('pause');

it('does not pause without conditions', function () {
    $shouldPause = (fn () => $this->shouldPause())->bindTo(new ConvertSubtitleJob($this->tempDir.'/test.vtt'), ConvertSubtitleJob::class);

    expect($shouldPause())->toBeFalse();
})->group('pause');

it('aborts if file is already srt', function () {
    $sourceFile = $this->tempDir.'/existing.srt';
    $srtContent = "1\n00:00:01,000 --> 00:00:04,000\nAlready SRT\n\n";
    File::put($sourceFile, $srtContent);

    $job = new ConvertSubtitleJob($sourceFile);
    $job->handle();

    expect($sourceFile)->toBeFile();
});
