<?php

use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('lists directories at root with depth=0', function () {
    $response = $this->getJson('/libraries/directories?path=/&depth=0');

    $response->assertOk();
    $response->assertJsonStructure([
        'path',
        'directories' => [
            '*' => ['name', 'path'],
        ],
    ]);
    expect($response->json('path'))->toBe('/');

    $dirs = $response->json('directories');
    expect($dirs)->toBeArray();
    foreach ($dirs as $dir) {
        expect($dir)->not->toHaveKey('children');
    }
});

it('returns nested children with depth > 0', function () {
    $dir = sys_get_temp_dir().'/depth-test-'.uniqid();
    mkdir($dir, 0755, true);
    mkdir($dir.'/sub1', 0755);
    mkdir($dir.'/sub1/nested', 0755);
    mkdir($dir.'/sub2', 0755);
    file_put_contents($dir.'/file.txt', 'test');

    $response = $this->getJson('/libraries/directories?path='.urlencode($dir).'&depth=5');

    $response->assertOk();
    $response->assertJsonStructure([
        'path',
        'directories' => [
            '*' => ['name', 'path', 'children'],
        ],
    ]);

    $directories = $response->json('directories');
    expect($directories)->toHaveCount(2);
    $sub1 = collect($directories)->firstWhere('name', 'sub1');
    expect($sub1)->not->toBeNull();
    expect($sub1['children'])->toHaveCount(1);
    expect($sub1['children'][0]['name'])->toBe('nested');

    $sub2 = collect($directories)->firstWhere('name', 'sub2');
    expect($sub2)->not->toBeNull();
    expect($sub2['children'])->toBe([]);
});

it('skips symlinked directories', function () {
    $dir = sys_get_temp_dir().'/symlink-test-'.uniqid();
    $target = sys_get_temp_dir().'/symlink-target-'.uniqid();
    mkdir($target, 0755, true);
    mkdir($dir, 0755, true);
    mkdir($dir.'/realdir', 0755);
    symlink($target, $dir.'/link');

    $response = $this->getJson('/libraries/directories?path='.urlencode($dir).'&depth=0');

    $response->assertOk();
    $dirs = $response->json('directories');
    $linkEntry = collect($dirs)->firstWhere('name', 'link');
    expect($linkEntry)->toBeNull();
    $realEntry = collect($dirs)->firstWhere('name', 'realdir');
    expect($realEntry)->not->toBeNull();
});

it('returns 404 for non-existent path', function () {
    $response = $this->getJson('/libraries/directories?path=/nonexistent-path-12345');

    $response->assertNotFound();
});

it('returns 422 for path traversal', function () {
    $response = $this->getJson('/libraries/directories?path=../../etc');

    $response->assertStatus(422);
});

it('returns 422 for home directory shortcut', function () {
    $response = $this->getJson('/libraries/directories?path=~/home');

    $response->assertStatus(422);
});
