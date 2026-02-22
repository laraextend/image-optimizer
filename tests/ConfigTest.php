<?php

test('config is loaded with correct defaults', function (): void {
    expect(config('media-toolkit.output_dir'))->toBe('media/optimized');

    // image.quality.*
    expect(config('media-toolkit.image.quality.webp'))->toBe(80);
    expect(config('media-toolkit.image.quality.avif'))->toBe(65);
    expect(config('media-toolkit.image.quality.jpg'))->toBe(82);
    expect(config('media-toolkit.image.quality.jpeg'))->toBe(82);
    expect(config('media-toolkit.image.quality.png'))->toBe(85);

    // image.responsive.*
    expect(config('media-toolkit.image.responsive.size_factors'))->toBe([0.5, 0.75, 1.0, 1.5, 2.0]);
    expect(config('media-toolkit.image.responsive.min_width'))->toBe(100);

    // image.defaults.*
    expect(config('media-toolkit.image.defaults.format'))->toBe('webp');
    expect(config('media-toolkit.image.defaults.picture_formats'))->toBe(['avif', 'webp']);
    expect(config('media-toolkit.image.defaults.fallback_format'))->toBe('jpg');
    expect(config('media-toolkit.image.defaults.loading'))->toBe('lazy');
    expect(config('media-toolkit.image.defaults.fetchpriority'))->toBe('auto');
    expect(config('media-toolkit.image.defaults.sizes'))->toBe('100vw');
});

test('config values can be overridden at runtime', function (): void {
    config(['media-toolkit.image.quality.webp' => 90]);
    config(['media-toolkit.output_dir' => 'custom/images']);

    expect(config('media-toolkit.image.quality.webp'))->toBe(90);
    expect(config('media-toolkit.output_dir'))->toBe('custom/images');
});
