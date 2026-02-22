<?php

use Laraextend\MediaToolkit\Facades\Media;

/**
 * Extract the absolute filesystem path from an <img src="..."> HTML snippet.
 * The src may be a full URL (http://localhost/...) or a bare path (/media/...).
 */
function srcToFilePath(string $html): string
{
    preg_match('/\ssrc="([^"]+)"/', $html, $matches);
    $urlPath = parse_url($matches[1] ?? '', PHP_URL_PATH) ?? '';

    return public_path(ltrim($urlPath, '/'));
}

// ─────────────────────────────────────────────────────────────
//  FIT — cover + center-crop to exact frame
// ─────────────────────────────────────────────────────────────

test('fit produces exactly the requested dimensions in html attributes', function (): void {
    $html = Media::image($this->landscapeImage)
        ->fit(200, 200)
        ->format('jpg')
        ->html(alt: 'Fit square');

    expect($html)
        ->toContain('<img')
        ->toContain('width="200"')
        ->toContain('height="200"');

    $this->assertGeneratedPublicFileExists($html);
});

test('fit generates a file with exact pixel dimensions', function (): void {
    $html = Media::image($this->landscapeImage)
        ->fit(200, 100)
        ->format('jpg')
        ->html(alt: 'Fit landscape');

    $this->assertGeneratedPublicFileExists($html);

    [$w, $h] = getimagesize(srcToFilePath($html));

    expect($w)->toBe(200);
    expect($h)->toBe(100);
});

test('fit portrait image into landscape frame generates exact dimensions', function (): void {
    $html = Media::image($this->portraitImage)
        ->fit(300, 150)
        ->format('jpg')
        ->html(alt: 'Fit portrait');

    $this->assertGeneratedPublicFileExists($html);

    [$w, $h] = getimagesize(srcToFilePath($html));

    expect($w)->toBe(300);
    expect($h)->toBe(150);
});

// ─────────────────────────────────────────────────────────────
//  CROP — region extraction, no scaling
// ─────────────────────────────────────────────────────────────

test('crop produces expected dimensions in html attributes', function (): void {
    $html = Media::image($this->landscapeImage)
        ->crop(200, 100)
        ->format('jpg')
        ->html(alt: 'Crop topleft');

    expect($html)
        ->toContain('<img')
        ->toContain('width="200"')
        ->toContain('height="100"');

    $this->assertGeneratedPublicFileExists($html);
});

test('crop with center anchor generates correct pixel dimensions', function (): void {
    $html = Media::image($this->landscapeImage)
        ->crop(300, 150, 'center', 'center')
        ->format('jpg')
        ->html(alt: 'Crop center');

    $this->assertGeneratedPublicFileExists($html);

    [$w, $h] = getimagesize(srcToFilePath($html));

    expect($w)->toBe(300);
    expect($h)->toBe(150);
});

test('crop with pixel offset generates correct pixel dimensions', function (): void {
    $html = Media::image($this->landscapeImage)
        ->crop(200, 100, 100, 50)
        ->format('jpg')
        ->html(alt: 'Crop offset');

    $this->assertGeneratedPublicFileExists($html);

    [$w, $h] = getimagesize(srcToFilePath($html));

    expect($w)->toBe(200);
    expect($h)->toBe(100);
});

test('crop with right and bottom anchors generates correct pixel dimensions', function (): void {
    $html = Media::image($this->landscapeImage)
        ->crop(200, 100, 'right', 'bottom')
        ->format('jpg')
        ->html(alt: 'Crop right-bottom');

    $this->assertGeneratedPublicFileExists($html);

    [$w, $h] = getimagesize(srcToFilePath($html));

    expect($w)->toBe(200);
    expect($h)->toBe(100);
});

test('crop can be combined with a filter', function (): void {
    $html = Media::image($this->landscapeImage)
        ->crop(300, 150, 'center', 'center')
        ->grayscale()
        ->format('jpg')
        ->html(alt: 'Crop + Grayscale');

    expect($html)->toContain('<img');
    $this->assertGeneratedPublicFileExists($html);
});

// ─────────────────────────────────────────────────────────────
//  STRETCH — exact resize, aspect ratio ignored
// ─────────────────────────────────────────────────────────────

test('stretch produces exactly the requested dimensions in html attributes', function (): void {
    $html = Media::image($this->portraitImage)
        ->stretch(300, 100)
        ->format('jpg')
        ->html(alt: 'Stretch');

    expect($html)
        ->toContain('<img')
        ->toContain('width="300"')
        ->toContain('height="100"');

    $this->assertGeneratedPublicFileExists($html);
});

test('stretch generates a file with exact pixel dimensions ignoring aspect ratio', function (): void {
    $html = Media::image($this->portraitImage)
        ->stretch(200, 200)
        ->format('jpg')
        ->html(alt: 'Stretch square');

    $this->assertGeneratedPublicFileExists($html);

    [$w, $h] = getimagesize(srcToFilePath($html));

    expect($w)->toBe(200);
    expect($h)->toBe(200);
});

test('stretch landscape source into portrait frame ignores aspect ratio', function (): void {
    $html = Media::image($this->landscapeImage)
        ->stretch(100, 400)
        ->format('jpg')
        ->html(alt: 'Stretch portrait');

    $this->assertGeneratedPublicFileExists($html);

    [$w, $h] = getimagesize(srcToFilePath($html));

    expect($w)->toBe(100);
    expect($h)->toBe(400);
});

// ─────────────────────────────────────────────────────────────
//  RESIZE — proportional, with aspect-ratio preservation
// ─────────────────────────────────────────────────────────────

test('resize by width preserves aspect ratio in html attributes', function (): void {
    // landscape is 800×400, resize(width:400) → 400×200
    $html = Media::image($this->landscapeImage)
        ->resize(width: 400)
        ->format('jpg')
        ->html(alt: 'Resize width');

    expect($html)
        ->toContain('width="400"')
        ->toContain('height="200"');
});

test('resize by height preserves aspect ratio in html attributes', function (): void {
    // landscape is 800×400, resize(height:200) → 400×200
    $html = Media::image($this->landscapeImage)
        ->resize(height: 200)
        ->format('jpg')
        ->html(alt: 'Resize height');

    expect($html)
        ->toContain('width="400"')
        ->toContain('height="200"');
});

test('resize generates a file with proportional pixel dimensions', function (): void {
    $html = Media::image($this->landscapeImage)
        ->resize(width: 400)
        ->format('jpg')
        ->html(alt: 'Resize file');

    $this->assertGeneratedPublicFileExists($html);

    [$w, $h] = getimagesize(srcToFilePath($html));

    expect($w)->toBe(400);
    expect($h)->toBe(200);
});
