<?php

use Laraextend\MediaToolkit\Cache\ManifestCache;
use Laraextend\MediaToolkit\Components\Image\Img;
use Laraextend\MediaToolkit\Components\Image\ImgUrl;
use Laraextend\MediaToolkit\Components\Image\Picture;
use Laraextend\MediaToolkit\Components\Image\ResponsiveImg;
use Laraextend\MediaToolkit\Facades\Media;
use Laraextend\MediaToolkit\Processing\ImageProcessor;
use Laraextend\MediaToolkit\Rendering\ImageHtmlRenderer;

test('ImageProcessor is registered as singleton', function (): void {
    $instance1 = app(ImageProcessor::class);
    $instance2 = app(ImageProcessor::class);

    expect($instance1)->toBe($instance2);
});

test('ManifestCache is registered as singleton', function (): void {
    $instance1 = app(ManifestCache::class);
    $instance2 = app(ManifestCache::class);

    expect($instance1)->toBe($instance2);
});

test('ImageHtmlRenderer is registered as singleton', function (): void {
    $instance1 = app(ImageHtmlRenderer::class);
    $instance2 = app(ImageHtmlRenderer::class);

    expect($instance1)->toBe($instance2);
});

test('media-toolkit facade accessor is bound', function (): void {
    expect(app('media-toolkit'))->toBeObject();
});

test('Media facade resolves an ImageBuilder via image()', function (): void {
    $builder = Media::image('some/path.jpg');

    expect($builder)->toBeInstanceOf(\Laraextend\MediaToolkit\Builders\ImageBuilder::class);
});

test('config is merged from package', function (): void {
    expect(config('media-toolkit'))->not->toBeNull();
    expect(config('media-toolkit.image'))->toBeArray();
    expect(config('media-toolkit.image.quality'))->toBeArray();
});

test('blade component namespace media is registered', function (): void {
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
