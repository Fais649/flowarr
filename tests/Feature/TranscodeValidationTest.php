<?php

use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

it('rejects invalid outputs before originals can be replaced', function (string $fault) {
    $dir = storage_path('app/validate_'.uniqid());
    File::ensureDirectoryExists($dir);
    $source = ['format' => ['duration' => '60', 'size' => '1000000'], 'streams' => [['codec_type' => 'video'], ['codec_type' => 'audio'], ['codec_type' => 'subtitle']]];
    $output = $source;
    if ($fault === 'duration') {
        $output['format']['duration'] = '30';
    } elseif ($fault === 'tracks') {
        array_pop($output['streams']);
    } else {
        $output['format']['size'] = '200000000';
    }
    File::put($dir.'/source.json', json_encode($source));
    File::put($dir.'/output.json', json_encode($output));
    try {
        $process = new Process(['php', base_path('scripts/validate_transcode.php'), $dir.'/source.json', $dir.'/output.json', '12000000']);
        $process->run();
        expect($process->isSuccessful())->toBeFalse();
    } finally {
        File::deleteDirectory($dir);
    }
})->with(['duration', 'tracks', 'bitrate']);
