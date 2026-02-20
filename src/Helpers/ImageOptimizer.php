<?php

namespace Laraexten\ImageOptimizer\Helpers;

use Illuminate\Support\Facades\File;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\Encoders\AvifEncoder;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\EncoderInterface;

class ImageOptimizer
{
    protected ImageManager $manager;

    /**
     * Breakpoint multipliers relative to specified width.
     */
    protected const SIZE_FACTORS = [0.5, 0.75, 1.0, 1.5, 2.0];

    /**
     * Quality settings per format.
     */
    protected const QUALITY = [
        'webp' => 80,
        'avif' => 65,
        'jpg' => 82,
        'jpeg' => 82,
        'png' => 85,
    ];

    /**
     * MIME-Types for <source type="...">
     */
    protected const MIME_TYPES = [
        'webp' => 'image/webp',
        'avif' => 'image/avif',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
    ];

    protected string $publicPath;

    protected string $outputDir;

    protected string $driverName;

    public function __construct()
    {
        $this->driverName = extension_loaded('imagick') ? 'imagick' : 'gd';
        $driver = $this->driverName === 'imagick' ? new ImagickDriver : new GdDriver;
        $this->manager = new ImageManager($driver);

        $this->publicPath = public_path();
        $this->outputDir = 'img/optimized';
    }

    /**
     * Checks if a format is supported by the current driver.
     */
    protected function supportsFormat(string $format): bool
    {
        $format = strtolower($format);

        if ($format === 'avif') {
            if ($this->driverName === 'gd') {
                return function_exists('imageavif');
            }
            // Imagick: check if AVIF is in the list of supported formats
            if ($this->driverName === 'imagick') {
                return in_array('AVIF', \Imagick::queryFormats('AVIF'));
            }

            return false;
        }

        if ($format === 'webp') {
            if ($this->driverName === 'gd') {
                return function_exists('imagewebp');
            }

            return true;
        }

        // jpg, png - always supported
        return true;
    }

    /**
     * Selects a safe fallback format if the desired one is not supported.
     */
    protected function safeFormat(string $format): string
    {
        if ($this->supportsFormat($format)) {
            return $format;
        }

        // Fallback chain: avif → webp → jpg
        if ($format === 'avif' && $this->supportsFormat('webp')) {
            return 'webp';
        }

        return 'jpg';
    }

    // ─────────────────────────────────────────────────────────────
    //  PUBLIC API
    // ─────────────────────────────────────────────────────────────

    /**
     * img() - Single optimized image, WITHOUT srcset.
     * Resize + compression, but only one file.
     */
    public function renderSingle(
        string $src,
        string $alt = '',
        ?int $width = null,
        ?int $height = null,
        string $class = '',
        string $format = 'webp',
        string $loading = 'lazy',
        string $fetchpriority = 'auto',
        ?string $id = null,
        bool $original = false,
        array $attributes = [],
    ): string {
        $sourcePath = base_path($src);

        if (! File::exists($sourcePath)) {
            return $this->error($src);
        }

        // Original? → simply copy to public, no processing
        if ($original) {
            $url = $this->copyOriginal($sourcePath);

            return $this->buildSimpleImgTag($url, $alt, $width, $height, $class, $loading, $fetchpriority, $id, $attributes);
        }

        $sourceModified = File::lastModified($sourcePath);
        $format = $this->safeFormat(strtolower($format));

        // Generate ONLY ONE variant (exactly the desired width)
        $variants = $this->getOrCreateVariants($sourcePath, $sourceModified, $width, $format, singleOnly: true);

        if (empty($variants)) {
            return $this->error($src);
        }

        $variant = $this->findClosestVariant($variants, $width ?? $variants[0]['width']);

        // Calculate height if not specified
        if ($height === null && $width !== null && $variant) {
            $height = (int) round($width * ($variant['height'] / $variant['width']));
        }

        return $this->buildSimpleImgTag($variant['url'], $alt, $width, $height, $class, $loading, $fetchpriority, $id, $attributes);
    }

    /**
     * responsive_img() - <img> with srcset (responsive behavior).
     */
    public function renderResponsive(
        string $src,
        string $alt = '',
        ?int $width = null,
        ?int $height = null,
        string $class = '',
        string $format = 'webp',
        string $loading = 'lazy',
        string $fetchpriority = 'auto',
        string $sizes = '100vw',
        ?string $id = null,
        bool $original = false,
        array $attributes = [],
    ): string {
        $sourcePath = base_path($src);

        if (! File::exists($sourcePath)) {
            return $this->error($src);
        }

        if ($original) {
            $url = $this->copyOriginal($sourcePath);

            return $this->buildSimpleImgTag($url, $alt, $width, $height, $class, $loading, $fetchpriority, $id, $attributes);
        }

        $sourceModified = File::lastModified($sourcePath);
        $format = $this->safeFormat(strtolower($format));
        $variants = $this->getOrCreateVariants($sourcePath, $sourceModified, $width, $format);

        if (empty($variants)) {
            return $this->error($src);
        }

        return $this->buildResponsiveImgTag($variants, $alt, $width, $height, $class, $loading, $fetchpriority, $sizes, $id, $attributes);
    }

    /**
     * picture() - <picture> with multiple <source> per format + fallback <img>.
     *
     * Generates:
     *   <picture>
     *     <source type="image/avif" srcset="...avif 225w, ...avif 450w" sizes="...">
     *     <source type="image/webp" srcset="...webp 225w, ...webp 450w" sizes="...">
     *     <img src="...jpg" srcset="...jpg 225w, ..." ... >
     *   </picture>
     */
    public function renderPicture(
        string $src,
        string $alt = '',
        ?int $width = null,
        ?int $height = null,
        string $class = '',          // class for <picture>
        string $imgClass = '',       // class for <img>
        string $sourceClass = '',    // class for all <source> elements
        array $formats = ['avif', 'webp'],
        string $fallbackFormat = 'jpg',
        ?string $loading = null,      // null = automatically decide
        string $fetchpriority = 'auto',
        string $sizes = '100vw',
        ?string $id = null,
        bool $original = false,
        array $attributes = [],
    ): string {
        // Resolve loading: explicit > automatic from fetchpriority
        $resolvedLoading = $loading ?? ($fetchpriority === 'high' ? 'eager' : 'lazy');
        $sourcePath = base_path($src);

        if (! File::exists($sourcePath)) {
            return $this->error($src);
        }

        // Original - no <picture> needed, simple <img>
        if ($original) {
            $url = $this->copyOriginal($sourcePath);

            return $this->buildSimpleImgTag($url, $alt, $width, $height, $class, $loading, $fetchpriority, $id, $attributes);
        }

        $sourceModified = File::lastModified($sourcePath);

        // Variants per format - skip unsupported formats
        $formatVariants = [];
        foreach ($formats as $fmt) {
            $fmt = strtolower($fmt);
            if (! $this->supportsFormat($fmt)) {
                continue;
            }
            $variants = $this->getOrCreateVariants($sourcePath, $sourceModified, $width, $fmt);
            if (! empty($variants)) {
                $formatVariants[$fmt] = $variants;
            }
        }

        // Fallback (e.g. jpg for old browsers)
        $fallbackFormat = $this->safeFormat(strtolower($fallbackFormat));
        $fallbackVariants = $this->getOrCreateVariants($sourcePath, $sourceModified, $width, $fallbackFormat);

        if (empty($fallbackVariants) && empty($formatVariants)) {
            return $this->error($src);
        }

        return $this->buildPictureTag(
            $formatVariants, $fallbackVariants, $fallbackFormat,
            $alt, $width, $height, $class, $imgClass, $sourceClass, $resolvedLoading, $fetchpriority, $sizes, $id, $attributes,
        );
    }

    /**
     * img_url() - Return only the URL.
     */
    public function url(
        string $src,
        ?int $width = null,
        string $format = 'webp',
        bool $original = false,
    ): string {
        $sourcePath = base_path($src);

        if (! File::exists($sourcePath)) {
            return '';
        }

        if ($original) {
            return $this->copyOriginal($sourcePath);
        }

        $sourceModified = File::lastModified($sourcePath);
        $format = $this->safeFormat(strtolower($format));
        $variants = $this->getOrCreateVariants($sourcePath, $sourceModified, $width, $format, singleOnly: true);

        if (empty($variants)) {
            return '';
        }

        $targetWidth = $width ?? $variants[0]['width'];

        return $this->findClosestVariant($variants, $targetWidth)['url'] ?? '';
    }

    // ─────────────────────────────────────────────────────────────
    //  COPY ORIGINAL FILE (no processing)
    // ─────────────────────────────────────────────────────────────

    /**
     * Copies the original file unchanged to public/.
     * Timestamp check avoids unnecessary copying.
     */
    protected function copyOriginal(string $sourcePath): string
    {
        $fileName = basename($sourcePath);
        $hash = substr(md5($sourcePath), 0, 8);
        $destDir = $this->publicPath.'/'.$this->outputDir.'/originals';
        $destFile = $destDir.'/'.$hash.'-'.$fileName;
        $urlPath = '/'.$this->outputDir.'/originals/'.$hash.'-'.$fileName;

        File::ensureDirectoryExists($destDir, 0755, true);

        if (! File::exists($destFile) || File::lastModified($sourcePath) > File::lastModified($destFile)) {
            File::copy($sourcePath, $destFile);
        }

        return $urlPath;
    }

    // ─────────────────────────────────────────────────────────────
    //  VARIANT GENERATION + CACHING
    // ─────────────────────────────────────────────────────────────

    protected function getOrCreateVariants(
        string $sourcePath,
        int $sourceModified,
        ?int $displayWidth,
        string $format,
        bool $singleOnly = false,
    ): array {
        $hash = $this->getCacheHash($sourcePath, $displayWidth, $format, $singleOnly);
        $cacheDir = $this->publicPath.'/'.$this->outputDir.'/'.$hash;

        $manifestPath = $cacheDir.'/manifest.json';

        if (File::exists($manifestPath)) {
            $manifest = json_decode(File::get($manifestPath), true);

            if (($manifest['source_modified'] ?? 0) === $sourceModified) {
                return $manifest['variants'] ?? [];
            }

            File::deleteDirectory($cacheDir);
        }

        return $this->createVariants($sourcePath, $sourceModified, $displayWidth, $format, $cacheDir, $singleOnly);
    }

    protected function createVariants(
        string $sourcePath,
        int $sourceModified,
        ?int $displayWidth,
        string $format,
        string $cacheDir,
        bool $singleOnly = false,
    ): array {
        File::ensureDirectoryExists($cacheDir, 0755, true);

        $image = $this->manager->read($sourcePath);
        $originalWidth = $image->width();
        $originalHeight = $image->height();
        $aspectRatio = $originalHeight / $originalWidth;

        $baseWidth = $displayWidth ?? $originalWidth;

        if ($singleOnly) {
            $widths = [min($baseWidth, $originalWidth)];
        } else {
            $widths = $this->calculateWidths($baseWidth, $originalWidth);
        }

        $variants = [];
        $baseName = pathinfo($sourcePath, PATHINFO_FILENAME);
        $quality = self::QUALITY[$format] ?? 80;

        foreach ($widths as $w) {
            $h = (int) round($w * $aspectRatio);
            $fileName = "{$baseName}-{$w}w.{$format}";
            $filePath = $cacheDir.'/'.$fileName;
            $urlPath = '/'.$this->outputDir.'/'.basename($cacheDir).'/'.$fileName;

            $resized = $this->manager->read($sourcePath)->scale(width: $w);
            $encoded = $resized->encode($this->getEncoder($format, $quality));
            $encoded->save($filePath);

            // dd($urlPath);

            $variants[] = [
                'url' => $urlPath,
                'width' => $w,
                'height' => $h,
                'size' => filesize($filePath),
            ];
        }

        $manifest = [
            'source' => $sourcePath,
            'source_modified' => $sourceModified,
            'format' => $format,
            'display_width' => $displayWidth,
            'single_only' => $singleOnly,
            'original_width' => $originalWidth,
            'original_height' => $originalHeight,
            'created_at' => now()->toIso8601String(),
            'variants' => $variants,
        ];

        File::put($cacheDir.'/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));

        return $variants;
    }

    protected function calculateWidths(int $baseWidth, int $originalWidth): array
    {
        $widths = [];

        foreach (self::SIZE_FACTORS as $factor) {
            $w = (int) round($baseWidth * $factor);

            if ($w > $originalWidth || $w < 100) {
                continue;
            }

            $widths[] = $w;
        }

        if ($originalWidth <= $baseWidth * 2 && ! in_array($originalWidth, $widths)) {
            $widths[] = $originalWidth;
        }

        $widths = array_unique($widths);
        sort($widths);

        if (empty($widths)) {
            $widths[] = min($baseWidth, $originalWidth);
        }

        return $widths;
    }

    // ─────────────────────────────────────────────────────────────
    //  HTML-TAG BUILDER
    // ─────────────────────────────────────────────────────────────

    /**
     * Simple <img> WITHOUT srcset.
     */
    protected function buildSimpleImgTag(
        string $url,
        string $alt,
        ?int $width,
        ?int $height,
        string $class,
        string $loading,
        string $fetchpriority,
        ?string $id,
        array $attributes,
    ): string {
        $attrs = [
            'src' => $url,
            'alt' => $alt,
            'loading' => $loading,
            'decoding' => 'async',
            'fetchpriority' => $fetchpriority,
        ];

        if ($width) {
            $attrs['width'] = $width;
        }
        if ($height) {
            $attrs['height'] = $height;
        }
        if ($class) {
            $attrs['class'] = $class;
        }
        if ($id) {
            $attrs['id'] = $id;
        }

        if ($fetchpriority === 'high' && $loading === 'lazy') {
            $attrs['loading'] = 'eager';
        }

        $attrs = array_merge($attrs, $attributes);

        return $this->renderTag('img', $attrs);
    }

    /**
     * <img> WITH srcset.
     */
    protected function buildResponsiveImgTag(
        array $variants,
        string $alt,
        ?int $width,
        ?int $height,
        string $class,
        string $loading,
        string $fetchpriority,
        string $sizes,
        ?string $id,
        array $attributes,
    ): string {
        $targetWidth = $width ?? $variants[0]['width'];
        $defaultVariant = $this->findClosestVariant($variants, $targetWidth);

        $srcset = $this->buildSrcset($variants);

        if ($height === null && $width !== null && $defaultVariant) {
            $height = (int) round($width * ($defaultVariant['height'] / $defaultVariant['width']));
        }

        $attrs = [
            'src' => $defaultVariant['url'],
            'srcset' => $srcset,
            'sizes' => $sizes,
            'alt' => $alt,
            'loading' => $loading,
            'decoding' => 'async',
            'fetchpriority' => $fetchpriority,
        ];

        if ($width) {
            $attrs['width'] = $width;
        }
        if ($height) {
            $attrs['height'] = $height;
        }
        if ($class) {
            $attrs['class'] = $class;
        }
        if ($id) {
            $attrs['id'] = $id;
        }

        if ($fetchpriority === 'high' && $loading === 'lazy') {
            $attrs['loading'] = 'eager';
        }

        $attrs = array_merge($attrs, $attributes);

        return $this->renderTag('img', $attrs);
    }

    /**
     * <picture> with <source> per format + fallback <img>.
     */
    protected function buildPictureTag(
        array $formatVariants,
        array $fallbackVariants,
        string $fallbackFormat,
        string $alt,
        ?int $width,
        ?int $height,
        string $class,        // class for <picture>
        string $imgClass,     // class for <img>
        string $sourceClass,  // class for <source>
        string $loading,
        string $fetchpriority,
        string $sizes,
        ?string $id,
        array $attributes,
    ): string {
        if ($height === null && $width !== null && ! empty($fallbackVariants)) {
            $v = $this->findClosestVariant($fallbackVariants, $width);
            $height = $v ? (int) round($width * ($v['height'] / $v['width'])) : null;
        }

        // Open <picture>
        $pictureAttrs = [];
        if ($class) {
            $pictureAttrs['class'] = $class;
        }
        if ($id) {
            $pictureAttrs['id'] = $id;
        }

        $html = $this->renderTag('picture', $pictureAttrs)."\n";

        // <source> pro modernem Format
        foreach ($formatVariants as $fmt => $variants) {
            $srcset = $this->buildSrcset($variants);
            $type = self::MIME_TYPES[$fmt] ?? "image/{$fmt}";

            $sourceAttrs = [
                'type' => $type,
                'srcset' => $srcset,
                'sizes' => $sizes,
            ];
            if ($sourceClass) {
                $sourceAttrs['class'] = $sourceClass;
            }

            $html .= '    '.$this->renderTag('source', $sourceAttrs)."\n";
        }

        // Fallback <img>
        $fallbackDefault = ! empty($fallbackVariants)
            ? $this->findClosestVariant($fallbackVariants, $width ?? $fallbackVariants[0]['width'])
            : null;

        $imgAttrs = [
            'src' => $fallbackDefault['url'] ?? '',
            'alt' => $alt,
            'loading' => $loading,
            'decoding' => 'async',
            'fetchpriority' => $fetchpriority,
        ];

        if (! empty($fallbackVariants)) {
            $imgAttrs['srcset'] = $this->buildSrcset($fallbackVariants);
            $imgAttrs['sizes'] = $sizes;
        }

        if ($width) {
            $imgAttrs['width'] = $width;
        }
        if ($height) {
            $imgAttrs['height'] = $height;
        }
        if ($imgClass) {
            $imgAttrs['class'] = $imgClass;
        }

        $imgAttrs = array_merge($imgAttrs, $attributes);

        $html .= '    '.$this->renderTag('img', $imgAttrs)."\n";
        $html .= '</picture>';

        return $html;
    }

    // ─────────────────────────────────────────────────────────────
    //  HELPER METHODS
    // ─────────────────────────────────────────────────────────────

    protected function buildSrcset(array $variants): string
    {
        $parts = [];
        foreach ($variants as $v) {
            $parts[] = $v['url'].' '.$v['width'].'w';
        }

        return implode(', ', $parts);
    }

    protected function renderTag(string $tag, array $attrs): string
    {

        if (isset($attrs['src'])) {
            $attrs["src"] = asset($attrs["src"]);
        }

        if (isset($attrs['srcset'])) {
            $srcsetParts = explode(', ', $attrs['srcset']);
            foreach ($srcsetParts as &$part) {
                [$url, $descriptor] = explode(' ', $part);
                $part = asset($url).' '.$descriptor;
            }
            $attrs['srcset'] = implode(', ', $srcsetParts);
        }

        $html = "<{$tag}";
        foreach ($attrs as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $html .= ' '.e($key).'="'.e($value).'"';
        }
        $html .= '>';

        return $html;
    }

    protected function findClosestVariant(array $variants, int $targetWidth): ?array
    {
        $closest = null;
        $minDiff = PHP_INT_MAX;

        foreach ($variants as $v) {
            $diff = abs($v['width'] - $targetWidth);
            if ($diff < $minDiff) {
                $minDiff = $diff;
                $closest = $v;
            }
        }

        return $closest;
    }

    protected function getCacheHash(string $sourcePath, ?int $width, string $format, bool $singleOnly = false): string
    {
        $key = implode('|', [
            $sourcePath,
            $width ?? 'auto',
            $format,
            $singleOnly ? 'single' : 'multi',
        ]);

        return substr(md5($key), 0, 12);
    }

    protected function getEncoder(string $format, int $quality): EncoderInterface
    {
        return match ($format) {
            'webp' => new WebpEncoder(quality: $quality),
            'avif' => new AvifEncoder(quality: $quality),
            'jpg', 'jpeg' => new JpegEncoder(quality: $quality),
            'png' => new PngEncoder,
            default => new WebpEncoder(quality: $quality),
        };
    }

    protected function error(string $src): string
    {
        if (app()->isLocal()) {
            return "<!-- IMG ERROR: File not found: {$src} -->";
        }

        return '';
    }

    // ─────────────────────────────────────────────────────────────
    //  CACHE MANAGEMENT
    // ─────────────────────────────────────────────────────────────

    public function clearCache(): int
    {
        $dir = $this->publicPath.'/'.$this->outputDir;

        if (! File::isDirectory($dir)) {
            return 0;
        }

        $dirs = File::directories($dir);
        $count = count($dirs);

        File::deleteDirectory($dir);

        return $count;
    }

    public function warmCache(): array
    {
        $dir = $this->publicPath.'/'.$this->outputDir;
        $results = ['regenerated' => 0, 'skipped' => 0, 'errors' => []];

        if (! File::isDirectory($dir)) {
            return $results;
        }

        foreach (File::directories($dir) as $cacheDir) {
            $manifestPath = $cacheDir.'/manifest.json';

            if (! File::exists($manifestPath)) {
                continue;
            }

            $manifest = json_decode(File::get($manifestPath), true);
            $sourcePath = $manifest['source'] ?? null;

            if (! $sourcePath || ! File::exists($sourcePath)) {
                $results['errors'][] = "Source file not found: {$sourcePath}";

                continue;
            }

            $currentModified = File::lastModified($sourcePath);

            if ($currentModified !== ($manifest['source_modified'] ?? 0)) {
                File::deleteDirectory($cacheDir);
                $this->createVariants(
                    $sourcePath,
                    $currentModified,
                    $manifest['display_width'] ?? null,
                    $manifest['format'] ?? 'webp',
                    $cacheDir,
                    $manifest['single_only'] ?? false,
                );
                $results['regenerated']++;
            } else {
                $results['skipped']++;
            }
        }

        return $results;
    }
}
