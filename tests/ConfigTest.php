<?php

test('config is loaded with correct defaults', function (): void {
    expect(config('media-toolkit.output_dir'))->toBe('img/optimized');
    expect(config('media-toolkit.quality.webp'))->toBe(80);
    expect(config('media-toolkit.quality.avif'))->toBe(65);
    expect(config('media-toolkit.quality.jpg'))->toBe(82);
    expect(config('media-toolkit.quality.jpeg'))->toBe(82);
    expect(config('media-toolkit.quality.png'))->toBe(85);

    // Nested under 'responsive'
    expect(config('media-toolkit.responsive.size_factors'))->toBe([0.5, 0.75, 1.0, 1.5, 2.0]);
    expect(config('media-toolkit.responsive.min_width'))->toBe(100);

    // Nested under 'defaults'
    expect(config('media-toolkit.defaults.format'))->toBe('webp');
    expect(config('media-toolkit.defaults.picture_formats'))->toBe(['avif', 'webp']);
    expect(config('media-toolkit.defaults.fallback_format'))->toBe('jpg');
    expect(config('media-toolkit.defaults.loading'))->toBe('lazy');
    expect(config('media-toolkit.defaults.fetchpriority'))->toBe('auto');
    expect(config('media-toolkit.defaults.sizes'))->toBe('100vw');
});

test('config values can be overridden at runtime', function (): void {
    config(['media-toolkit.quality.webp' => 90]);
    config(['media-toolkit.output_dir' => 'custom/images']);

    expect(config('media-toolkit.quality.webp'))->toBe(90);
    expect(config('media-toolkit.output_dir'))->toBe('custom/images');
});
