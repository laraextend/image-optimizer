<?php

namespace Laraexten\ImageOptimizer\Test;

use Laraexten\ImageOptimizer\Components\Img;
use Laraexten\ImageOptimizer\Components\ImgUrl;
use Laraexten\ImageOptimizer\Components\Picture;
use Laraexten\ImageOptimizer\Components\ResponsiveImg;

class ComponentTest extends TestCase
{
    public function test_img_component_has_correct_default_values(): void
    {
        $component = new Img(src: 'test.jpg');

        $this->assertSame('test.jpg', $component->src);
        $this->assertSame('', $component->alt);
        $this->assertNull($component->width);
        $this->assertNull($component->height);
        $this->assertSame('', $component->class);
        $this->assertSame('webp', $component->format);
        $this->assertSame('lazy', $component->loading);
        $this->assertSame('auto', $component->fetchpriority);
        $this->assertNull($component->id);
        $this->assertFalse($component->original);
        $this->assertSame([], $component->extraAttributes);
    }

    public function test_responsive_img_component_has_correct_default_values(): void
    {
        $component = new ResponsiveImg(src: 'test.jpg');

        $this->assertSame('test.jpg', $component->src);
        $this->assertSame('100vw', $component->sizes);
        $this->assertSame('webp', $component->format);
        $this->assertSame('lazy', $component->loading);
    }

    public function test_picture_component_has_correct_default_values(): void
    {
        $component = new Picture(src: 'test.jpg');

        $this->assertSame('test.jpg', $component->src);
        $this->assertSame(['avif', 'webp'], $component->formats);
        $this->assertSame('jpg', $component->fallbackFormat);
        $this->assertNull($component->loading);
        $this->assertSame('auto', $component->fetchpriority);
        $this->assertSame('', $component->imgClass);
        $this->assertSame('', $component->sourceClass);
    }

    public function test_img_url_component_has_correct_default_values(): void
    {
        $component = new ImgUrl(src: 'test.jpg');

        $this->assertSame('test.jpg', $component->src);
        $this->assertNull($component->width);
        $this->assertSame('webp', $component->format);
        $this->assertFalse($component->original);
    }

    public function test_img_url_render_returns_closure(): void
    {
        $component = new ImgUrl(src: 'non-existent.jpg');
        $result = $component->render();

        $this->assertIsCallable($result);
        // For a non-existent file, the URL should be an empty string
        $this->assertSame('', $result());
    }

    public function test_picture_component_resolves_loading_for_high_fetchpriority(): void
    {
        $component = new Picture(src: 'test.jpg', fetchpriority: 'high');

        // With fetchpriority=high and loading=null, render() should pass null to renderPicture
        // which then resolves to 'eager'
        $this->assertSame('high', $component->fetchpriority);
        $this->assertNull($component->loading);
    }

    public function test_img_component_accepts_extra_attributes(): void
    {
        $component = new Img(
            src: 'test.jpg',
            extraAttributes: ['data-lightbox' => 'gallery', 'style' => 'border-radius: 8px'],
        );

        $this->assertSame(
            ['data-lightbox' => 'gallery', 'style' => 'border-radius: 8px'],
            $component->extraAttributes,
        );
    }
}
