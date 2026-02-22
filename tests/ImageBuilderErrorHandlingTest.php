<?php

use Laraextend\MediaToolkit\Cache\ManifestCache;
use Laraextend\MediaToolkit\Exceptions\MediaBuilderException;
use Laraextend\MediaToolkit\Facades\Media;
use Laraextend\MediaToolkit\Processing\ImageProcessor;

// ─────────────────────────────────────────────────────────────
//  HELPER — bind a ManifestCache that always throws
// ─────────────────────────────────────────────────────────────

function bindFailingCache(): void
{
    /** @var ImageProcessor $processor */
    $processor = app()->make(ImageProcessor::class);
    $outputDir = $processor->normalizeOutputDir(config('media-toolkit.output_dir', 'media/optimized'));

    $failing = new class (
        public_path(),
        $outputDir,
        [0.5, 0.75, 1.0, 1.5, 2.0],
        100,
        $processor,
    ) extends ManifestCache {
        public function getOrCreate(
            string $sourcePath,
            int    $sourceModified,
            ?int   $displayWidth,
            string $format,
            bool   $singleOnly,
            array  $operations,
            string $operationsFingerprint,
            int    $quality,
            bool   $noCache = false,
        ): array {
            throw new \RuntimeException('Forced failure.');
        }
    };

    app()->instance(ManifestCache::class, $failing);
}

/**
 * Decode the base64 SVG embedded in a placeholder <img> tag and return its text content.
 * Returns null when the HTML does not contain a data URI.
 */
function decodePlaceholderText(string $html): ?string
{
    if (! preg_match('/src="data:image\/svg\+xml;base64,([^"]+)"/', $html, $m)) {
        return null;
    }

    return base64_decode($m[1]) ?: null;
}

// ─────────────────────────────────────────────────────────────
//  on_not_found = 'placeholder' (default)
// ─────────────────────────────────────────────────────────────

test('on_not_found placeholder renders inline SVG data URI', function (): void {
    $this->setPackageConfig(['image' => ['errors' => ['on_not_found' => 'placeholder']]]);

    $html = Media::image('does/not/exist.jpg')->resize(width: 400)->html(alt: 'Missing');

    expect($html)->toContain('<img')->toContain('data:image/svg+xml;base64,');
});

test('on_not_found placeholder SVG contains not_found_text', function (): void {
    $this->setPackageConfig(['image' => ['errors' => ['on_not_found' => 'placeholder']]]);

    $html = Media::image('does/not/exist.jpg')->resize(width: 400)->html(alt: 'Missing');

    expect(decodePlaceholderText($html))->toContain('Media could not be found.');
});

test('on_not_found placeholder respects custom not_found_text from config', function (): void {
    $this->setPackageConfig([
        'image' => [
            'errors' => [
                'on_not_found'   => 'placeholder',
                'not_found_text' => 'Foto nicht gefunden',
            ],
        ],
    ]);

    $html = Media::image('does/not/exist.jpg')->resize(width: 400)->html(alt: 'Missing');

    expect(decodePlaceholderText($html))->toContain('Foto nicht gefunden');
});

// ─────────────────────────────────────────────────────────────
//  on_not_found = 'broken'
// ─────────────────────────────────────────────────────────────

test('on_not_found broken renders img tag without SVG data URI', function (): void {
    $this->setPackageConfig(['image' => ['errors' => ['on_not_found' => 'broken']]]);

    $html = Media::image('does/not/exist.jpg')->resize(width: 400)->html(alt: 'Missing');

    expect($html)->toContain('<img');
    expect($html)->not->toContain('data:image/svg+xml');
});

// ─────────────────────────────────────────────────────────────
//  on_not_found = 'exception'
// ─────────────────────────────────────────────────────────────

test('on_not_found exception throws MediaBuilderException from html()', function (): void {
    $this->setPackageConfig(['image' => ['errors' => ['on_not_found' => 'exception']]]);

    expect(fn () => Media::image('does/not/exist.jpg')->resize(width: 400)->html(alt: 'Missing'))
        ->toThrow(MediaBuilderException::class);
});

test('on_not_found exception throws MediaBuilderException from url()', function (): void {
    $this->setPackageConfig(['image' => ['errors' => ['on_not_found' => 'exception']]]);

    expect(fn () => Media::image('does/not/exist.jpg')->resize(width: 400)->url())
        ->toThrow(MediaBuilderException::class);
});

// ─────────────────────────────────────────────────────────────
//  on_error = 'placeholder' (default)
// ─────────────────────────────────────────────────────────────

test('on_error placeholder renders inline SVG data URI', function (): void {
    $this->setPackageConfig(['image' => ['errors' => ['on_error' => 'placeholder']]]);
    bindFailingCache();

    $html = Media::image($this->landscapeImage)->resize(width: 400)->format('jpg')->html(alt: 'Err');

    expect($html)->toContain('<img')->toContain('data:image/svg+xml;base64,');
});

test('on_error placeholder SVG contains error_text', function (): void {
    $this->setPackageConfig(['image' => ['errors' => ['on_error' => 'placeholder']]]);
    bindFailingCache();

    $html = Media::image($this->landscapeImage)->resize(width: 400)->format('jpg')->html(alt: 'Err');

    expect(decodePlaceholderText($html))->toContain('Media could not be displayed!');
});

test('on_error placeholder respects custom error_text from config', function (): void {
    $this->setPackageConfig([
        'image' => [
            'errors' => [
                'on_error'   => 'placeholder',
                'error_text' => 'Bild wird verarbeitet',
            ],
        ],
    ]);
    bindFailingCache();

    $html = Media::image($this->landscapeImage)->resize(width: 400)->format('jpg')->html(alt: 'Err');

    expect(decodePlaceholderText($html))->toContain('Bild wird verarbeitet');
});

test('on_error placeholder width attribute reflects requested resize dimension', function (): void {
    $this->setPackageConfig(['image' => ['errors' => ['on_error' => 'placeholder']]]);
    bindFailingCache();

    $html = Media::image($this->landscapeImage)->resize(width: 300)->format('jpg')->html(alt: 'Err');

    expect($html)->toContain('width="300"');
});

// ─────────────────────────────────────────────────────────────
//  on_error = 'broken'
// ─────────────────────────────────────────────────────────────

test('on_error broken renders img tag without SVG data URI', function (): void {
    $this->setPackageConfig(['image' => ['errors' => ['on_error' => 'broken']]]);
    bindFailingCache();

    $html = Media::image($this->landscapeImage)->resize(width: 400)->format('jpg')->html(alt: 'Err');

    expect($html)->toContain('<img');
    expect($html)->not->toContain('data:image/svg+xml');
});

// ─────────────────────────────────────────────────────────────
//  on_error = 'exception'
// ─────────────────────────────────────────────────────────────

test('on_error exception throws MediaBuilderException', function (): void {
    $this->setPackageConfig(['image' => ['errors' => ['on_error' => 'exception']]]);
    bindFailingCache();

    expect(fn () => Media::image($this->landscapeImage)->resize(width: 400)->format('jpg')->html(alt: 'Err'))
        ->toThrow(MediaBuilderException::class);
});

// ─────────────────────────────────────────────────────────────
//  WATERMARK — URL / web-path as source
// ─────────────────────────────────────────────────────────────

test('watermark accepts a web path returned by url()', function (): void {
    // Generate the watermark file first so it actually exists on disk.
    $logoUrl = Media::image($this->squareImage)->resize(width: 100)->format('jpg')->url();

    expect($logoUrl)->not->toBeEmpty();

    $html = Media::image($this->landscapeImage)
        ->resize(width: 400)
        ->watermark($logoUrl, 'bottom-right', 5, 80)
        ->format('jpg')
        ->html(alt: 'WM via URL');

    expect($html)->toContain('<img')->toContain('alt="WM via URL"');

    // Must produce a different cached file than the same image without watermark.
    $htmlNoWm = Media::image($this->landscapeImage)->resize(width: 400)->format('jpg')->html(alt: 'No WM');

    preg_match('/\ssrc="([^"]+)"/', $html, $mA);
    preg_match('/\ssrc="([^"]+)"/', $htmlNoWm, $mB);

    expect($mA[1] ?? '')->not->toBe($mB[1] ?? '');
});

test('watermark accepts a full http URL', function (): void {
    $urlPath     = Media::image($this->squareImage)->resize(width: 100)->format('jpg')->url();
    $fullHttpUrl = 'http://localhost' . $urlPath;

    $html = Media::image($this->landscapeImage)
        ->resize(width: 400)
        ->watermark($fullHttpUrl, 'top-left')
        ->format('jpg')
        ->html(alt: 'WM HTTP');

    expect($html)->toContain('<img');
});

test('watermark still accepts a base_path relative path', function (): void {
    $html = Media::image($this->landscapeImage)
        ->resize(width: 400)
        ->watermark($this->squareImage, 'center', 0, 50)
        ->format('jpg')
        ->html(alt: 'WM base_path');

    expect($html)->toContain('<img');
});
