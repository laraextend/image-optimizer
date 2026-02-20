<?php

namespace Laraexten\ImageOptimizer\Test;

class ConfigTest extends TestCase
{
    public function test_config_is_loaded_with_defaults(): void
    {
        $this->assertSame('img/optimized', config('image-optimizer.output_dir'));
        $this->assertSame(80, config('image-optimizer.quality.webp'));
        $this->assertSame(65, config('image-optimizer.quality.avif'));
        $this->assertSame(82, config('image-optimizer.quality.jpg'));
        $this->assertSame(82, config('image-optimizer.quality.jpeg'));
        $this->assertSame(85, config('image-optimizer.quality.png'));
        $this->assertSame([0.5, 0.75, 1.0, 1.5, 2.0], config('image-optimizer.size_factors'));
        $this->assertSame(100, config('image-optimizer.min_width'));
        $this->assertSame('webp', config('image-optimizer.default_format'));
        $this->assertSame(['avif', 'webp'], config('image-optimizer.picture_formats'));
        $this->assertSame('jpg', config('image-optimizer.fallback_format'));
        $this->assertSame('lazy', config('image-optimizer.loading'));
        $this->assertSame('auto', config('image-optimizer.fetchpriority'));
        $this->assertSame('100vw', config('image-optimizer.sizes'));
    }

    public function test_config_values_can_be_overridden(): void
    {
        config(['image-optimizer.quality.webp' => 90]);
        config(['image-optimizer.output_dir' => 'custom/images']);

        $this->assertSame(90, config('image-optimizer.quality.webp'));
        $this->assertSame('custom/images', config('image-optimizer.output_dir'));
    }
}
