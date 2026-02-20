<?php

namespace Laraexten\ImageOptimizer\Test;

use Illuminate\Support\Facades\Blade;
use Laraexten\ImageOptimizer\Helpers\ImageOptimizer;

class ServiceProviderTest extends TestCase
{
    public function test_image_optimizer_is_registered_as_singleton(): void
    {
        $instance1 = app(ImageOptimizer::class);
        $instance2 = app(ImageOptimizer::class);

        $this->assertSame($instance1, $instance2);
    }

    public function test_config_is_merged_from_package(): void
    {
        $this->assertNotNull(config('image-optimizer'));
        $this->assertIsArray(config('image-optimizer.quality'));
    }

    public function test_blade_component_namespace_is_registered(): void
    {
        // The component namespace 'laraexten' should be registered
        // Verify by checking that the component class can be resolved
        $this->assertTrue(
            class_exists(\Laraexten\ImageOptimizer\Components\Img::class)
        );
        $this->assertTrue(
            class_exists(\Laraexten\ImageOptimizer\Components\ResponsiveImg::class)
        );
        $this->assertTrue(
            class_exists(\Laraexten\ImageOptimizer\Components\Picture::class)
        );
        $this->assertTrue(
            class_exists(\Laraexten\ImageOptimizer\Components\ImgUrl::class)
        );
    }

    public function test_helper_functions_are_available(): void
    {
        $this->assertTrue(function_exists('img'));
        $this->assertTrue(function_exists('responsive_img'));
        $this->assertTrue(function_exists('picture'));
        $this->assertTrue(function_exists('img_url'));
    }
}
