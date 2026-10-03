<?php

use App\ExecutionStatus;
use App\Models\Execution;
use App\Models\Library;
use App\Services\OriginalReplacement;
use App\Services\StreamTracker;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->dir = storage_path('app/replacement_'.uniqid());
    File::makeDirectory($this->dir, 0777, true);
    $this->source = $this->dir.'/episode.mkv';
    $this->copy = $this->dir.'/episode_hevc.mkv';
    File::put($this->source, 'original');
    File::put($this->copy, 'validated output');
    clearstatcache();
    $this->execution = Execution::factory()->create([
        'file_path' => $this->source, 'status' => ExecutionStatus::COMPLETED,
        'replacement_status' => 'pending',
        'replacement' => [
            'policy' => 'idle', 'output' => $this->copy, 'hash' => hash_file('sha256', $this->copy),
            'size' => filesize($this->source), 'mtime' => filemtime($this->source), 'inode' => fileinode($this->source),
        ],
    ]);
});

afterEach(function () {
    File::deleteDirectory($this->dir);
});

it('retains both files while streaming and replaces once idle', function () {
    app(StreamTracker::class)->start('jellyfin', 'test');
    expect(app(OriginalReplacement::class)->replace($this->execution))->toBeFalse();
    expect(File::get($this->source))->toBe('original');
    app(StreamTracker::class)->stop('jellyfin', 'test');
    $this->artisan('originals:replace')->assertSuccessful();
    expect(File::get($this->source))->toBe('validated output')
        ->and($this->copy)->not->toBeFile()
        ->and($this->execution->fresh()->replacement_status)->toBe('replaced');
});

it('handles daytime and overnight windows with an exclusive end', function (string $time, bool $expected) {
    $this->travelTo(now()->setTimeFromTimeString($time));
    expect(app(OriginalReplacement::class)->canReplace(['policy' => 'window', 'start' => '22:00', 'end' => '06:00']))->toBe($expected);
})->with([['21:59', false], ['22:00', true], ['01:00', true], ['06:00', false]]);

it('does not replace during playback even inside a replacement window', function () {
    $this->travelTo(now()->setTime(23, 0));
    app(StreamTracker::class)->start('jellyfin', 'test');
    expect(app(OriginalReplacement::class)->canReplace(['policy' => 'window', 'start' => '22:00', 'end' => '06:00']))->toBeFalse();
});

it('refuses changed originals and outputs', function (string $which) {
    File::put($which === 'source' ? $this->source : $this->copy, 'changed contents');
    expect(app(OriginalReplacement::class)->replace($this->execution))->toBeFalse()
        ->and($this->execution->fresh()->replacement_status)->toBe('failed');
    expect($this->source)->toBeFile()->and($this->copy)->toBeFile();
})->with(['source', 'output']);

it('does not clobber an existing mkv target for an mp4 original', function () {
    $source = $this->dir.'/episode.mp4';
    rename($this->source, $source);
    File::put($this->source, 'unrelated target');
    $this->execution->update(['file_path' => $source]);
    expect(app(OriginalReplacement::class)->replace($this->execution))->toBeFalse();
    expect(File::get($this->source))->toBe('unrelated target')->and($source)->toBeFile()->and($this->copy)->toBeFile();
});

it('uses per-library handling ahead of worker settings and preserves legacy behavior', function () {
    $service = app(OriginalReplacement::class);
    expect($service->policy(Library::factory()->make(['original_handling' => 'keep']), true))->toBe('keep')
        ->and($service->policy(Library::factory()->make(['original_handling' => null]), true))->toBe('immediate');
});
