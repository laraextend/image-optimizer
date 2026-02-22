<?php

use Laraextend\MediaToolkit\Helpers\ImageOptimizer;

test('configured output directory and default format are used', function (): void {
    $this->setPackageConfig([
        'output_dir' => 'custom/optimized',
        'defaults' => [
            'format' => 'jpg',
            'loading' => 'eager',
            'fetchpriority' => 'low',
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

test('invalid config values fall back to safe defaults', function (): void {
    $this->setPackageConfig([
        'driver' => 'invalid-driver',
        'output_dir' => '',
        'responsive' => [
            'size_factors' => ['x', -2, 0, 'bad', -1],
            'min_width' => 0,
        ],
        'defaults' => [
            'format' => 'invalid-format',
            'loading' => 'invalid-loading',
            'fetchpriority' => 'invalid-priority',
            'sizes' => '',
            'picture_formats' => ['invalid-source-format'],
            'fallback_format' => 'invalid-fallback',
        ],
    ]);

    $html = responsive_img(
        src: $this->landscapeImage,
        alt: 'Fallback config',
        width: 400,
        format: 'jpg',
    );

    expect($html)
        ->toContain('/img/optimized/')
        ->toContain('loading="lazy"')
        ->toContain('fetchpriority="auto"')
        ->toContain('sizes="100vw"')
        ->toContain(' 200w')
        ->toContain(' 300w');
});

test('memory limit fallback adds metadata attributes', function (): void {
    $optimizer = new class extends ImageOptimizer {
        protected function shouldBypassOptimization(string $sourcePath, ?int $targetWidth, ?int $targetHeight): bool
        {
            return true;
        }
    };

    $html = $optimizer->renderSingle(
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

test('optimization error fallback adds metadata attributes', function (): void {
    $optimizer = new class extends ImageOptimizer {
        protected function getOrCreateVariants(
            string $sourcePath,
            int $sourceModified,
            ?int $displayWidth,
            string $format,
            bool $singleOnly = false,
        ): array {
            throw new \RuntimeException('Forced optimizer failure.');
        }
    };

    $html = $optimizer->renderSingle(
        src: $this->landscapeImage,
        alt: 'Error fallback',
        width: 400,
        format: 'jpg',
    );

    expect($html)
        ->toContain('/originals/')
        ->toContain('data-media-toolkit-status="original-fallback"')
        ->toContain('data-media-toolkit-reason="optimization-error"');
});

test('custom fallback metadata attributes are not overwritten', function (): void {
    $optimizer = new class extends ImageOptimizer {
        protected function shouldBypassOptimization(string $sourcePath, ?int $targetWidth, ?int $targetHeight): bool
        {
            return true;
        }
    };

    $html = $optimizer->renderSingle(
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