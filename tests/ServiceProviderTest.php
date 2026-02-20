<?php

use Laraextend\ImageOptimizer\Components\Img;
use Laraextend\ImageOptimizer\Components\ImgUrl;
use Laraextend\ImageOptimizer\Components\Picture;
use Laraextend\ImageOptimizer\Components\ResponsiveImg;
use Laraextend\ImageOptimizer\Helpers\ImageOptimizer;

test('image optimizer is registered as singleton', function (): void {
    $instance1 = app(ImageOptimizer::class);
    $instance2 = app(ImageOptimizer::class);

    expect($instance1)->toBe($instance2);
});

test('config is merged from package', function (): void {
    expect(config('image-optimizer'))->not->toBeNull();
    expect(config('image-optimizer.quality'))->toBeArray();
});

test('blade component namespace laraextend is registered', function (): void {
    expect(class_exists(Img::class))->toBeTrue();
    expect(class_exists(ResponsiveImg::class))->toBeTrue();
    expect(class_exists(Picture::class))->toBeTrue();
    expect(class_exists(ImgUrl::class))->toBeTrue();
});

test('helper functions are available', function (): void {
    expect(function_exists('img'))->toBeTrue();
    expect(function_exists('responsive_img'))->toBeTrue();
    expect(function_exists('picture'))->toBeTrue();
    expect(function_exists('img_url'))->toBeTrue();
});
