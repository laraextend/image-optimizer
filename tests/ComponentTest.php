<?php

use Laraextend\MediaToolkit\Components\Img;
use Laraextend\MediaToolkit\Components\ImgUrl;
use Laraextend\MediaToolkit\Components\Picture;
use Laraextend\MediaToolkit\Components\ResponsiveImg;

test('img component has correct default values', function (): void {
    $component = new Img(src: 'test.jpg');

    expect($component->src)->toBe('test.jpg');
    expect($component->alt)->toBe('');
    expect($component->width)->toBeNull();
    expect($component->height)->toBeNull();
    expect($component->class)->toBe('');
    expect($component->format)->toBe('webp');
    expect($component->loading)->toBe('lazy');
    expect($component->fetchpriority)->toBe('auto');
    expect($component->id)->toBeNull();
    expect($component->original)->toBeFalse();
    expect($component->extraAttributes)->toBe([]);
});

test('responsive-img component has correct default values', function (): void {
    $component = new ResponsiveImg(src: 'test.jpg');

    expect($component->src)->toBe('test.jpg');
    expect($component->sizes)->toBe('100vw');
    expect($component->format)->toBe('webp');
    expect($component->loading)->toBe('lazy');
});

test('picture component has correct default values', function (): void {
    $component = new Picture(src: 'test.jpg');

    expect($component->src)->toBe('test.jpg');
    expect($component->formats)->toBe(['avif', 'webp']);
    expect($component->fallbackFormat)->toBe('jpg');
    expect($component->loading)->toBeNull();
    expect($component->fetchpriority)->toBe('auto');
    expect($component->imgClass)->toBe('');
    expect($component->sourceClass)->toBe('');
});

test('img-url component has correct default values', function (): void {
    $component = new ImgUrl(src: 'test.jpg');

    expect($component->src)->toBe('test.jpg');
    expect($component->width)->toBeNull();
    expect($component->format)->toBe('webp');
    expect($component->original)->toBeFalse();
});

test('img-url render returns a callable that produces empty string for missing file', function (): void {
    $component = new ImgUrl(src: 'non-existent.jpg');
    $result = $component->render();

    expect($result)->toBeCallable();
    expect($result())->toBe('');
});

test('picture component resolves loading to null when fetchpriority is high', function (): void {
    $component = new Picture(src: 'test.jpg', fetchpriority: 'high');

    expect($component->fetchpriority)->toBe('high');
    expect($component->loading)->toBeNull();
});

test('img component accepts extra attributes', function (): void {
    $component = new Img(
        src: 'test.jpg',
        extraAttributes: ['data-lightbox' => 'gallery', 'style' => 'border-radius: 8px'],
    );

    expect($component->extraAttributes)->toBe([
        'data-lightbox' => 'gallery',
        'style' => 'border-radius: 8px',
    ]);
});
