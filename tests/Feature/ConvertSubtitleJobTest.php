<?php

use App\Jobs\ConvertSubtitleJob;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->tempDir = storage_path('app/testing_subtitles_'.uniqid());
    File::makeDirectory($this->tempDir, 0777, true, true);
    Config::set('services.ffmpeg.bin', '/usr/bin/ffmpeg');
});

afterEach(function () {
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

it('aborts if file is already srt', function () {
    $sourceFile = $this->tempDir.'/existing.srt';
    $srtContent = "1\n00:00:01,000 --> 00:00:04,000\nAlready SRT\n\n";
    File::put($sourceFile, $srtContent);

    $job = new ConvertSubtitleJob($sourceFile);
    $job->handle();

    expect($sourceFile)->toBeFile();
});
