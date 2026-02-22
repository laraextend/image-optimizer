<?php

use Laraextend\MediaToolkit\Facades\Media;

// ─────────────────────────────────────────────────────────────
//  BASIC FILTERS — each produces a valid <img> tag and file
// ─────────────────────────────────────────────────────────────

test('grayscale filter renders valid img tag and generates a file', function (): void {
    $html = Media::image($this->landscapeImage)
        ->resize(width: 400)
        ->grayscale()
        ->format('jpg')
        ->html(alt: 'Grayscale');

    expect($html)->toContain('<img')->toContain('alt="Grayscale"');
    $this->assertGeneratedPublicFileExists($html);
});

test('sepia filter renders valid img tag and generates a file', function (): void {
    $html = Media::image($this->landscapeImage)
        ->resize(width: 400)
        ->sepia()
        ->format('jpg')
        ->html(alt: 'Sepia');

    expect($html)->toContain('<img')->toContain('alt="Sepia"');
    $this->assertGeneratedPublicFileExists($html);
});

test('negate filter renders valid img tag and generates a file', function (): void {
    $html = Media::image($this->landscapeImage)
        ->resize(width: 400)
        ->negate()
        ->format('jpg')
        ->html(alt: 'Negate');

    expect($html)->toContain('<img');
    $this->assertGeneratedPublicFileExists($html);
});

test('brightness filter renders valid img tag and generates a file', function (): void {
    $html = Media::image($this->landscapeImage)
        ->resize(width: 400)
        ->brightness(50)
        ->format('jpg')
        ->html(alt: 'Bright');

    expect($html)->toContain('<img');
    $this->assertGeneratedPublicFileExists($html);
});

test('contrast filter renders valid img tag and generates a file', function (): void {
    $html = Media::image($this->landscapeImage)
        ->resize(width: 400)
        ->contrast(20)
        ->format('jpg')
        ->html(alt: 'Contrast');

    expect($html)->toContain('<img');
    $this->assertGeneratedPublicFileExists($html);
});

test('colorize filter renders valid img tag and generates a file', function (): void {
    $html = Media::image($this->landscapeImage)
        ->resize(width: 400)
        ->colorize(50, -20, 30)
        ->format('jpg')
        ->html(alt: 'Colorize');

    expect($html)->toContain('<img');
    $this->assertGeneratedPublicFileExists($html);
});

test('blur filter renders valid img tag and generates a file', function (): void {
    $html = Media::image($this->landscapeImage)
        ->resize(width: 400)
        ->blur(3)
        ->format('jpg')
        ->html(alt: 'Blur');

    expect($html)->toContain('<img');
    $this->assertGeneratedPublicFileExists($html);
});

test('smooth filter renders valid img tag and generates a file', function (): void {
    $html = Media::image($this->landscapeImage)
        ->resize(width: 400)
        ->smooth(5)
        ->format('jpg')
        ->html(alt: 'Smooth');

    expect($html)->toContain('<img');
    $this->assertGeneratedPublicFileExists($html);
});

test('rotate filter renders valid img tag and generates a file', function (): void {
    $html = Media::image($this->squareImage)
        ->resize(width: 300)
        ->rotate(90)
        ->format('jpg')
        ->html(alt: 'Rotate');

    expect($html)->toContain('<img');
    $this->assertGeneratedPublicFileExists($html);
});

test('flipHorizontal filter renders valid img tag and generates a file', function (): void {
    $html = Media::image($this->landscapeImage)
        ->resize(width: 400)
        ->flipHorizontal()
        ->format('jpg')
        ->html(alt: 'FlipH');

    expect($html)->toContain('<img');
    $this->assertGeneratedPublicFileExists($html);
});

test('flipVertical filter renders valid img tag and generates a file', function (): void {
    $html = Media::image($this->landscapeImage)
        ->resize(width: 400)
        ->flipVertical()
        ->format('jpg')
        ->html(alt: 'FlipV');

    expect($html)->toContain('<img');
    $this->assertGeneratedPublicFileExists($html);
});

test('flipBoth filter renders valid img tag and generates a file', function (): void {
    $html = Media::image($this->landscapeImage)
        ->resize(width: 400)
        ->flipBoth()
        ->format('jpg')
        ->html(alt: 'FlipBoth');

    expect($html)->toContain('<img');
    $this->assertGeneratedPublicFileExists($html);
});

// ─────────────────────────────────────────────────────────────
//  FILTER STACKING
// ─────────────────────────────────────────────────────────────

test('multiple filters can be stacked together', function (): void {
    $html = Media::image($this->landscapeImage)
        ->resize(width: 400)
        ->grayscale()
        ->blur(2)
        ->brightness(30)
        ->format('jpg')
        ->html(alt: 'Stacked');

    expect($html)->toContain('<img');
    $this->assertGeneratedPublicFileExists($html);
});

test('different filter chains produce different cached files', function (): void {
    $htmlA = Media::image($this->landscapeImage)
        ->resize(width: 400)
        ->grayscale()
        ->format('jpg')
        ->html(alt: 'A');

    $htmlB = Media::image($this->landscapeImage)
        ->resize(width: 400)
        ->sepia()
        ->format('jpg')
        ->html(alt: 'B');

    expect($htmlA)->toContain('<img');
    expect($htmlB)->toContain('<img');

    // Different operation fingerprints → different cached file paths
    preg_match('/\ssrc="([^"]+)"/', $htmlA, $matchA);
    preg_match('/\ssrc="([^"]+)"/', $htmlB, $matchB);

    expect($matchA[1] ?? '')->not->toBe($matchB[1] ?? '');
});

test('filters without a size op use original image dimensions', function (): void {
    // landscape is 800×400; no resize → output keeps 800×400
    $html = Media::image($this->landscapeImage)
        ->grayscale()
        ->format('jpg')
        ->html(alt: 'No resize grayscale');

    expect($html)
        ->toContain('<img')
        ->toContain('width="800"')
        ->toContain('height="400"');
});

// ─────────────────────────────────────────────────────────────
//  WATERMARK
// ─────────────────────────────────────────────────────────────

test('watermark produces a valid img tag and generates a file', function (): void {
    $html = Media::image($this->landscapeImage)
        ->resize(width: 400)
        ->watermark($this->squareImage, 'bottom-right', 5, 80)
        ->format('jpg')
        ->html(alt: 'Watermarked');

    expect($html)->toContain('<img')->toContain('alt="Watermarked"');
    $this->assertGeneratedPublicFileExists($html);
});

test('watermark with different positions produces different cache files', function (): void {
    $htmlBR = Media::image($this->landscapeImage)
        ->resize(width: 400)
        ->watermark($this->squareImage, 'bottom-right')
        ->format('jpg')
        ->html(alt: 'BR');

    $htmlTL = Media::image($this->landscapeImage)
        ->resize(width: 400)
        ->watermark($this->squareImage, 'top-left')
        ->format('jpg')
        ->html(alt: 'TL');

    preg_match('/\ssrc="([^"]+)"/', $htmlBR, $matchBR);
    preg_match('/\ssrc="([^"]+)"/', $htmlTL, $matchTL);

    expect($matchBR[1] ?? '')->not->toBe($matchTL[1] ?? '');
});

test('watermark combined with grayscale generates a file', function (): void {
    $html = Media::image($this->landscapeImage)
        ->resize(width: 400)
        ->grayscale()
        ->watermark($this->squareImage, 'center', 0, 50)
        ->format('jpg')
        ->html(alt: 'Gray + Watermark');

    expect($html)->toContain('<img');
    $this->assertGeneratedPublicFileExists($html);
});
