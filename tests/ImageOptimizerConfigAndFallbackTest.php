<?php

use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;
use Laraextend\MediaToolkit\Cache\ManifestCache;
use Laraextend\MediaToolkit\Processing\ImageProcessor;

// ─────────────────────────────────────────────────────────────
//  HELPER: create an ImageProcessor whose driver mirrors the
//  current environment so the anonymous subclass can override
//  just the one method we need to change.
// ─────────────────────────────────────────────────────────────

function makeBypassingProcessor(): ImageProcessor
{
    $driverName = extension_loaded('imagick') ? 'imagick' : 'gd';
    $driver     = $driverName === 'imagick' ? new ImagickDriver() : new GdDriver();

    return new class ($driverName, new ImageManager($driver)) extends ImageProcessor {
        public function shouldBypassOptimization(string $sourcePath, ?int $targetWidth, ?int $targetHeight): bool
        {
            return true;
        }
    };
}

// ─────────────────────────────────────────────────────────────
//  CONFIG OVERRIDE TESTS
// ─────────────────────────────────────────────────────────────

test('configured output directory and default format are used', function (): void {
    $this->setPackageConfig([
        'output_dir' => 'custom/optimized',
        'image'      => [
            'defaults' => [
                'format'        => 'jpg',
                'loading'       => 'eager',
                'fetchpriority' => 'low',
            ],
        ],
    ]);

    $html = img(
        src: $this->landscapeImage,
        alt: 'Config defaults',
        width: 300,
    );

    expect($html)
        ->toContain('/custom/optimized/')
        ->toContain('.jpg')
        ->toContain('loading="eager"')
        ->toContain('fetchpriority="low"');
});

test('invalid loading and fetchpriority values are normalised to safe defaults', function (): void {
    $this->setPackageConfig([
        'image' => [
            'defaults' => [
                'loading'       => 'invalid-loading',
                'fetchpriority' => 'invalid-priority',
                'sizes'         => '',
            ],
        ],
    ]);

    $html = responsive_img(
        src: $this->landscapeImage,
        alt: 'Fallback config',
        width: 400,
        format: 'jpg',
    );

    expect($html)
        ->toContain('loading="lazy"')
        ->toContain('fetchpriority="auto"')
        ->toContain('sizes="100vw"');
});

test('invalid output_dir falls back to media/optimized', function (): void {
    $this->setPackageConfig(['output_dir' => '']);

    $html = img(
        src: $this->landscapeImage,
        alt: 'Invalid dir',
        width: 300,
        format: 'jpg',
    );

    expect($html)->toContain('/media/optimized/');
});

// ─────────────────────────────────────────────────────────────
//  MEMORY-LIMIT BYPASS FALLBACK
// ─────────────────────────────────────────────────────────────

test('memory limit default mode shows inline SVG placeholder', function (): void {
    $this->app->instance(ImageProcessor::class, makeBypassingProcessor());
    $this->app->forgetInstance(ManifestCache::class); // force rebuild with new processor

    $html = img(
        src: $this->landscapeImage,
        alt: 'Memory fallback',
        width: 400,
        format: 'jpg',
    );

    expect($html)->toContain('<img')->toContain('data:image/svg+xml;base64,');
});

test('memory limit with original mode adds metadata attributes', function (): void {
    $this->setPackageConfig(['image' => ['errors' => ['on_memory_limit' => 'original']]]);

    $this->app->instance(ImageProcessor::class, makeBypassingProcessor());
    $this->app->forgetInstance(ManifestCache::class);

    $html = img(
        src: $this->landscapeImage,
        alt: 'Memory fallback',
        width: 400,
        format: 'jpg',
    );

    expect($html)
        ->toContain('/originals/')
        ->toContain('data-media-toolkit-status="original-fallback"')
        ->toContain('data-media-toolkit-reason="memory-limit"');
});

test('memory limit with placeholder mode shows inline SVG', function (): void {
    $this->setPackageConfig(['image' => ['errors' => ['on_memory_limit' => 'placeholder']]]);

    $this->app->instance(ImageProcessor::class, makeBypassingProcessor());
    $this->app->forgetInstance(ManifestCache::class);

    $html = img(
        src: $this->landscapeImage,
        alt: 'Memory placeholder',
        width: 400,
        format: 'jpg',
    );

    expect($html)->toContain('<img')->toContain('data:image/svg+xml;base64,');
});

test('memory limit with broken mode shows img with original source path', function (): void {
    $this->setPackageConfig(['image' => ['errors' => ['on_memory_limit' => 'broken']]]);

    $this->app->instance(ImageProcessor::class, makeBypassingProcessor());
    $this->app->forgetInstance(ManifestCache::class);

    $html = img(
        src: $this->landscapeImage,
        alt: 'Memory broken',
        width: 400,
        format: 'jpg',
    );

    expect($html)->toContain('<img')->toContain($this->landscapeImage);
});

test('memory limit with exception mode throws MediaBuilderException', function (): void {
    $this->setPackageConfig(['image' => ['errors' => ['on_memory_limit' => 'exception']]]);

    $this->app->instance(ImageProcessor::class, makeBypassingProcessor());
    $this->app->forgetInstance(ManifestCache::class);

    expect(fn () => img(
        src: $this->landscapeImage,
        alt: 'Memory exception',
        width: 400,
        format: 'jpg',
    ))->toThrow(\Laraextend\MediaToolkit\Exceptions\MediaBuilderException::class);
});

// ─────────────────────────────────────────────────────────────
//  OPTIMISATION ERROR FALLBACK
// ─────────────────────────────────────────────────────────────

test('optimization error shows placeholder img by default', function (): void {
    /** @var ImageProcessor $processor */
    $processor = $this->app->make(ImageProcessor::class);
    $outputDir = $processor->normalizeOutputDir(config('media-toolkit.output_dir', 'media/optimized'));

    $failingCache = new class (
        public_path(),
        $outputDir,
        [0.5, 0.75, 1.0, 1.5, 2.0],
        100,
        $processor,
    ) extends ManifestCache {
        public function getOrCreate(
            string $sourcePath,
            int    $sourceModified,
            ?int   $displayWidth,
            string $format,
            bool   $singleOnly,
            array  $operations,
            string $operationsFingerprint,
            int    $quality,
            bool   $noCache = false,
        ): array {
            throw new \RuntimeException('Forced cache failure.');
        }
    };

    $this->app->instance(ManifestCache::class, $failingCache);

    $html = img(
        src: $this->landscapeImage,
        alt: 'Error fallback',
        width: 400,
        format: 'jpg',
    );

    // Default on_error = 'placeholder' → inline SVG data URI with "Image coming soon"
    expect($html)->toContain('<img')->toContain('data:image/svg+xml;base64,');
});

// ─────────────────────────────────────────────────────────────
//  CUSTOM FALLBACK METADATA NOT OVERWRITTEN
// ─────────────────────────────────────────────────────────────

test('custom fallback metadata attributes are not overwritten', function (): void {
    $this->setPackageConfig(['image' => ['errors' => ['on_memory_limit' => 'original']]]);
    $this->app->instance(ImageProcessor::class, makeBypassingProcessor());
    $this->app->forgetInstance(ManifestCache::class);

    $html = img(
        src: $this->landscapeImage,
        alt: 'Custom attributes',
        width: 400,
        format: 'jpg',
        attributes: [
            'data-media-toolkit-status' => 'manual',
            'data-media-toolkit-reason' => 'manual-reason',
        ],
    );

    expect($html)
        ->toContain('data-media-toolkit-status="manual"')
        ->toContain('data-media-toolkit-reason="manual-reason"')
        ->not->toContain('data-media-toolkit-status="original-fallback"');
});

// ─────────────────────────────────────────────────────────────
//  DIMENSION RESOLUTION
// ─────────────────────────────────────────────────────────────

test('picture without dimensions uses original size', function (): void {
    $html = picture(
        src: $this->portraitImage,
        alt: 'Original dimensions',
        formats: ['jpg'],
        fallbackFormat: 'jpg',
    );

    expect($html)->toContain('width="400"')->toContain('height="800"');
});

test('picture with only height calculates width proportionally', function (): void {
    $html = picture(
        src: $this->portraitImage,
        alt: 'Only height',
        height: 300,
        formats: ['jpg'],
        fallbackFormat: 'jpg',
    );

    expect($html)->toContain('width="150"')->toContain('height="300"');
});
