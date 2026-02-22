<?php

/**
 * Security regression tests.
 *
 * Covers:
 *   - Path traversal in source image path
 *   - Path traversal in watermark source
 *   - File extension whitelist enforcement
 *   - Log injection prevention (CRLF stripping)
 */

use Laraextend\MediaToolkit\Exceptions\MediaBuilderException;
use Laraextend\MediaToolkit\Facades\Media;

// ─────────────────────────────────────────────────────────────
//  PATH TRAVERSAL — SOURCE IMAGE
// ─────────────────────────────────────────────────────────────

test('path traversal with .. in source throws MediaBuilderException', function (): void {
    expect(fn () => Media::image('../../etc/passwd')->html(alt: 'x'))
        ->toThrow(MediaBuilderException::class, 'directory traversal');
});

test('path traversal with backslash .. in source throws MediaBuilderException', function (): void {
    expect(fn () => Media::image('..\\..\\windows\\system32\\config')->html(alt: 'x'))
        ->toThrow(MediaBuilderException::class, 'directory traversal');
});

test('null byte in source path throws MediaBuilderException', function (): void {
    expect(fn () => Media::image("resources/images/hero.jpg\0.php")->html(alt: 'x'))
        ->toThrow(MediaBuilderException::class, 'control characters');
});

test('path traversal in url() call throws MediaBuilderException', function (): void {
    expect(fn () => Media::image('../../etc/shadow')->url())
        ->toThrow(MediaBuilderException::class, 'directory traversal');
});

// ─────────────────────────────────────────────────────────────
//  FILE EXTENSION WHITELIST — SOURCE IMAGE
// ─────────────────────────────────────────────────────────────

test('php extension in source path throws MediaBuilderException', function (): void {
    expect(fn () => Media::image('config/database.php')->html(alt: 'x'))
        ->toThrow(MediaBuilderException::class, 'not an allowed image format');
});

test('env extension in source path throws MediaBuilderException', function (): void {
    expect(fn () => Media::image('.env')->html(alt: 'x'))
        ->toThrow(MediaBuilderException::class, 'not an allowed image format');
});

test('phtml extension in source path throws MediaBuilderException', function (): void {
    expect(fn () => Media::image('resources/evil.phtml')->html(alt: 'x'))
        ->toThrow(MediaBuilderException::class, 'not an allowed image format');
});

test('svg extension in source path throws MediaBuilderException', function (): void {
    // SVG is not processed by the image pipeline (can contain scripts)
    expect(fn () => Media::image('resources/icon.svg')->html(alt: 'x'))
        ->toThrow(MediaBuilderException::class, 'not an allowed image format');
});

test('allowed image extensions do not throw on extension check', function (string $ext): void {
    // File does not exist — must get not-found output, NOT a security exception.
    $html = Media::image("resources/images/test.{$ext}")->html(alt: 'x');
    // Should render a placeholder (not_found), not throw an extension error.
    expect($html)->toContain('<img');
})->with(['jpg', 'jpeg', 'png', 'webp', 'avif', 'bmp', 'tiff', 'gif']);

// ─────────────────────────────────────────────────────────────
//  PATH TRAVERSAL — WATERMARK SOURCE
// ─────────────────────────────────────────────────────────────

test('path traversal in watermark relative path throws MediaBuilderException', function (): void {
    expect(fn () =>
        Media::image($this->landscapeImage)
            ->watermark('../../etc/passwd')
            ->html(alt: 'x')
    )->toThrow(MediaBuilderException::class, 'directory traversal');
});

test('path traversal in watermark web path throws MediaBuilderException', function (): void {
    expect(fn () =>
        Media::image($this->landscapeImage)
            ->watermark('/../../etc/passwd')
            ->html(alt: 'x')
    )->toThrow(MediaBuilderException::class, 'directory traversal');
});

test('path traversal in watermark http url throws MediaBuilderException', function (): void {
    // Parse-URL extracts path "/../../etc/passwd" which would escape public_path()
    expect(fn () =>
        Media::image($this->landscapeImage)
            ->watermark('http://example.com/../../etc/passwd')
            ->html(alt: 'x')
    )->toThrow(MediaBuilderException::class, 'directory traversal');
});

test('null byte in watermark source throws MediaBuilderException', function (): void {
    expect(fn () =>
        Media::image($this->landscapeImage)
            ->watermark("resources/images/logo.png\0.php")
            ->html(alt: 'x')
    )->toThrow(MediaBuilderException::class, 'directory traversal');
});

// ─────────────────────────────────────────────────────────────
//  CONTROL CHARACTER / LOG INJECTION PREVENTION
// ─────────────────────────────────────────────────────────────

test('CRLF in source path is rejected before any processing', function (): void {
    // Paths with newlines are rejected immediately — they never reach the logger
    // or filesystem, which is the strongest possible protection against log injection.
    expect(fn () => Media::image("missing/file.jpg\nFAKE_ENTRY: injected")->html(alt: 'x'))
        ->toThrow(MediaBuilderException::class, 'control characters');
});

test('carriage return in source path is rejected', function (): void {
    expect(fn () => Media::image("resources/images/hero.jpg\r")->html(alt: 'x'))
        ->toThrow(MediaBuilderException::class, 'control characters');
});
