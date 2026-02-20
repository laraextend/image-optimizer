<?php

test('config is loaded with correct defaults', function (): void {
    expect(config('image-optimizer.output_dir'))->toBe('img/optimized');
    expect(config('image-optimizer.quality.webp'))->toBe(80);
    expect(config('image-optimizer.quality.avif'))->toBe(65);
    expect(config('image-optimizer.quality.jpg'))->toBe(82);
    expect(config('image-optimizer.quality.jpeg'))->toBe(82);
    expect(config('image-optimizer.quality.png'))->toBe(85);

    // Nested under 'responsive'
    expect(config('image-optimizer.responsive.size_factors'))->toBe([0.5, 0.75, 1.0, 1.5, 2.0]);
    expect(config('image-optimizer.responsive.min_width'))->toBe(100);

    // Nested under 'defaults'
    expect(config('image-optimizer.defaults.format'))->toBe('webp');
    expect(config('image-optimizer.defaults.picture_formats'))->toBe(['avif', 'webp']);
    expect(config('image-optimizer.defaults.fallback_format'))->toBe('jpg');
    expect(config('image-optimizer.defaults.loading'))->toBe('lazy');
    expect(config('image-optimizer.defaults.fetchpriority'))->toBe('auto');
    expect(config('image-optimizer.defaults.sizes'))->toBe('100vw');
});

test('config values can be overridden at runtime', function (): void {
    config(['image-optimizer.quality.webp' => 90]);
    config(['image-optimizer.output_dir' => 'custom/images']);

    expect(config('image-optimizer.quality.webp'))->toBe(90);
    expect(config('image-optimizer.output_dir'))->toBe('custom/images');
});
