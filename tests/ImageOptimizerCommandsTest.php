<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

test('artisan commands are registered', function (): void {
    $commands = Artisan::all();

    expect($commands)->toHaveKey('img:clear');
    expect($commands)->toHaveKey('img:warm');
});

test('img:clear deletes generated cache directories', function (): void {
    img(
        src: $this->landscapeImage,
        alt: 'For clear command',
        width: 300,
        format: 'jpg',
    );

    responsive_img(
        src: $this->portraitImage,
        alt: 'For clear command',
        width: 200,
        format: 'jpg',
    );

    $outputRoot = public_path(config('image-optimizer.output_dir'));
    expect(File::isDirectory($outputRoot))->toBeTrue();
    expect(File::directories($outputRoot))->not->toBeEmpty();

    $exitCode = Artisan::call('img:clear');
    expect($exitCode)->toBe(0);
    expect(Artisan::output())->toContain('cache entries deleted');

    expect(File::isDirectory($outputRoot))->toBeFalse();
});

test('img:warm regenerates outdated variants', function (): void {
    img(
        src: $this->landscapeImage,
        alt: 'Warm cache',
        width: 350,
        format: 'jpg',
    );

    $outputRoot = public_path(config('image-optimizer.output_dir'));
    $manifestFiles = File::glob($outputRoot.'/*/manifest.json');
    expect($manifestFiles)->not->toBeEmpty();

    $manifestPath = $manifestFiles[0];
    $manifestBefore = json_decode((string) File::get($manifestPath), true);
    expect($manifestBefore)->toBeArray();

    $sourcePath = $manifestBefore['source'] ?? null;
    expect($sourcePath)->toBeString();
    expect($sourcePath)->toBeFile();

    sleep(1);
    $this->createTestImage($sourcePath, 800, 400);

    $exitCode = Artisan::call('img:warm');
    expect($exitCode)->toBe(0);

    $manifestAfter = json_decode((string) File::get($manifestPath), true);
    expect($manifestAfter)->toBeArray();
    expect($manifestAfter['source_modified'] ?? null)->toBe(File::lastModified($sourcePath));
});

test('img:warm reports missing source files', function (): void {
    $outputRoot = public_path(config('image-optimizer.output_dir'));
    $fakeCacheDir = $outputRoot.'/missing-source-cache';
    File::ensureDirectoryExists($fakeCacheDir, 0755, true);

    $manifest = [
        'source' => base_path('tests/fixtures/image-optimizer/does-not-exist.jpg'),
        'source_modified' => time() - 100,
        'format' => 'jpg',
        'display_width' => 300,
        'single_only' => false,
        'variants' => [],
    ];

    File::put($fakeCacheDir.'/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));

    $exitCode = Artisan::call('img:warm');
    expect($exitCode)->toBe(0);
    expect(Artisan::output())->toContain('Source file not found');
});

