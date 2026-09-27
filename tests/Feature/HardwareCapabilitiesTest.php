<?php

use App\Services\HardwareCapabilities;

function capabilities(array $outputs, array $paths = []): HardwareCapabilities
{
    return new HardwareCapabilities(
        run: fn (array $command) => $outputs[implode(' ', array_slice($command, -2))] ?? $outputs[$command[0]] ?? null,
        pathExists: fn (string $path) => in_array($path, $paths, true),
    );
}

$encoders = <<<'TXT'
 V....D hevc_nvenc           NVIDIA NVENC hevc encoder (codec hevc)
 V....D hevc_vaapi           H.265/HEVC (VAAPI) (codec hevc)
 V....D libx265              libx265 H.265 / HEVC (codec hevc)
TXT;

it('selects NVENC when an NVIDIA GPU is present', function () use ($encoders) {
    $result = capabilities([
        '-hide_banner -version' => 'ffmpeg version 7.1-Jellyfin Copyright',
        '-hide_banner -encoders' => $encoders,
        'nvidia-smi' => 'GPU 0: NVIDIA GeForce',
        'ffprobe' => 'ffprobe version',
    ])->probe();

    expect($result['ffmpeg_version'])->toBe('7.1-Jellyfin')
        ->and($result['devices']['nvidia'])->toBeTrue()
        ->and($result['selected_encoder'])->toBe('hevc_nvenc');
});

it('selects VAAPI when a render device is present', function () use ($encoders) {
    $result = capabilities([
        '-hide_banner -version' => 'ffmpeg version 7.1',
        '-hide_banner -encoders' => $encoders,
    ], paths: ['/dev/dri/renderD128'])->probe();

    expect($result['devices'])->toBe(['nvidia' => false, 'vaapi' => true])
        ->and($result['selected_encoder'])->toBe('hevc_vaapi');
});

it('falls back to libx265 without a GPU or when forced to software', function () use ($encoders) {
    $outputs = ['-hide_banner -version' => 'ffmpeg version 7.1', '-hide_banner -encoders' => $encoders];

    expect(capabilities($outputs)->probe()['selected_encoder'])->toBe('libx265');

    config(['services.ffmpeg.enable_gpu_transcoding' => false]);
    $result = capabilities($outputs, paths: ['/dev/dri/renderD128'])->probe();

    expect($result['mode'])->toBe('software')->and($result['selected_encoder'])->toBe('libx265');
});

it('reports a missing ffmpeg', function () {
    $result = capabilities([])->probe();

    expect($result['ffmpeg_version'])->toBeNull()
        ->and($result['selected_encoder'])->toBeNull()
        ->and($result['encoders'])->toBe(['hevc_nvenc' => false, 'hevc_vaapi' => false, 'libx265' => false]);
});

it('detects the real ffmpeg in the test environment', function () {
    $result = app(HardwareCapabilities::class)->detect(fresh: true);

    expect($result['ffmpeg_version'])->not->toBeNull()
        ->and($result['encoders']['libx265'])->toBeTrue();
});
