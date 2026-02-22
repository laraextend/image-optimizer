<?php

use Laraextend\MediaToolkit\Exceptions\MediaBuilderException;
use Laraextend\MediaToolkit\Facades\Media;

// ─────────────────────────────────────────────────────────────
//  SIZE-OPERATION MUTUAL EXCLUSION
// ─────────────────────────────────────────────────────────────

test('resize after crop throws MediaBuilderException', function (): void {
    expect(fn () => Media::image('a.jpg')->crop(200, 200)->resize(width: 400))
        ->toThrow(MediaBuilderException::class);
});

test('crop after resize throws MediaBuilderException', function (): void {
    expect(fn () => Media::image('a.jpg')->resize(width: 400)->crop(200, 200))
        ->toThrow(MediaBuilderException::class);
});

test('fit after resize throws MediaBuilderException', function (): void {
    expect(fn () => Media::image('a.jpg')->resize(width: 400)->fit(200, 200))
        ->toThrow(MediaBuilderException::class);
});

test('stretch after resize throws MediaBuilderException', function (): void {
    expect(fn () => Media::image('a.jpg')->resize(width: 400)->stretch(200, 200))
        ->toThrow(MediaBuilderException::class);
});

test('resize after stretch throws MediaBuilderException', function (): void {
    expect(fn () => Media::image('a.jpg')->stretch(400, 200)->resize(width: 300))
        ->toThrow(MediaBuilderException::class);
});

test('fit after crop throws MediaBuilderException', function (): void {
    expect(fn () => Media::image('a.jpg')->crop(200, 200)->fit(400, 300))
        ->toThrow(MediaBuilderException::class);
});

// ─────────────────────────────────────────────────────────────
//  ORIGINAL MODE MUTUAL EXCLUSION
// ─────────────────────────────────────────────────────────────

test('format after original throws MediaBuilderException', function (): void {
    expect(fn () => Media::image('a.jpg')->original()->format('avif'))
        ->toThrow(MediaBuilderException::class);
});

test('quality after original throws MediaBuilderException', function (): void {
    expect(fn () => Media::image('a.jpg')->original()->quality(90))
        ->toThrow(MediaBuilderException::class);
});

test('resize after original throws MediaBuilderException', function (): void {
    expect(fn () => Media::image('a.jpg')->original()->resize(width: 400))
        ->toThrow(MediaBuilderException::class);
});

test('grayscale after original throws MediaBuilderException', function (): void {
    expect(fn () => Media::image('a.jpg')->original()->grayscale())
        ->toThrow(MediaBuilderException::class);
});

test('watermark after original throws MediaBuilderException', function (): void {
    expect(fn () => Media::image('a.jpg')->original()->watermark('logo.png'))
        ->toThrow(MediaBuilderException::class);
});

// ─────────────────────────────────────────────────────────────
//  UPSCALE CONSTRAINTS
// ─────────────────────────────────────────────────────────────

test('upscale without resize throws MediaBuilderException', function (): void {
    expect(fn () => Media::image('a.jpg')->upscale())
        ->toThrow(MediaBuilderException::class);
});

test('upscale after fit throws MediaBuilderException', function (): void {
    expect(fn () => Media::image('a.jpg')->fit(400, 300)->upscale())
        ->toThrow(MediaBuilderException::class);
});

test('upscale after crop throws MediaBuilderException', function (): void {
    expect(fn () => Media::image('a.jpg')->crop(200, 200)->upscale())
        ->toThrow(MediaBuilderException::class);
});

// ─────────────────────────────────────────────────────────────
//  VALID COMBINATIONS (should NOT throw)
// ─────────────────────────────────────────────────────────────

test('resize followed by upscale is valid', function (): void {
    expect(fn () => Media::image('a.jpg')->resize(width: 400)->upscale())
        ->not->toThrow(MediaBuilderException::class);
});

test('resize with both dimensions is valid', function (): void {
    expect(fn () => Media::image('a.jpg')->resize(width: 800, height: 600))
        ->not->toThrow(MediaBuilderException::class);
});

test('original followed by noCache is valid', function (): void {
    expect(fn () => Media::image('a.jpg')->original()->noCache())
        ->not->toThrow(MediaBuilderException::class);
});

test('multiple filters are stackable', function (): void {
    expect(fn () => Media::image('a.jpg')->grayscale()->blur(3)->brightness(20))
        ->not->toThrow(MediaBuilderException::class);
});

test('resize followed by filter then picture is valid', function (): void {
    expect(fn () => Media::image('a.jpg')->resize(width: 800)->grayscale()->picture())
        ->not->toThrow(MediaBuilderException::class);
});

test('resize requires at least one dimension', function (): void {
    expect(fn () => Media::image('a.jpg')->resize())
        ->toThrow(MediaBuilderException::class);
});
