<?php

use Laraextend\MediaToolkit\Components\Img;
use Laraextend\MediaToolkit\Components\ImgUrl;
use Laraextend\MediaToolkit\Components\Picture;
use Laraextend\MediaToolkit\Components\ResponsiveImg;
use Laraextend\MediaToolkit\Helpers\ImageOptimizer;

test('image optimizer is registered as singleton', function (): void {
    $instance1 = app(ImageOptimizer::class);
    $instance2 = app(ImageOptimizer::class);

    expect($instance1)->toBe($instance2);
});

test('config is merged from package', function (): void {
    expect(config('media-toolkit'))->not->toBeNull();
    expect(config('media-toolkit.quality'))->toBeArray();
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
