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
use Throwable;

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

    protected const DEFAULT_DRIVER = 'auto';

    protected const DEFAULT_OUTPUT_DIR = 'img/optimized';

    protected const DEFAULT_MIN_WIDTH = 100;

    protected const DEFAULT_FORMAT = 'webp';

    protected const DEFAULT_PICTURE_FORMATS = ['avif', 'webp'];

    protected const DEFAULT_FALLBACK_FORMAT = 'jpg';

    protected const DEFAULT_LOADING = 'lazy';

    protected const DEFAULT_FETCHPRIORITY = 'auto';

    protected const DEFAULT_SIZES = '100vw';

    protected const ALLOWED_DRIVERS = ['auto', 'gd', 'imagick'];

    protected const ALLOWED_FORMATS = ['webp', 'avif', 'jpg', 'jpeg', 'png'];

    protected const ALLOWED_LOADING = ['lazy', 'eager'];

    protected const ALLOWED_FETCHPRIORITY = ['auto', 'high', 'low'];

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

    protected array $sizeFactors;

    protected int $minWidth;

    protected array $quality;

    protected string $defaultFormat;

    protected array $defaultPictureFormats;

    protected string $defaultFallbackFormat;

    protected string $defaultLoading;

    protected string $defaultFetchpriority;

    protected string $defaultSizes;

    public function __construct()
    {
        $config = config('image-optimizer', []);
        if (! is_array($config)) {
            $config = [];
        }

        $responsiveConfig = is_array($config['responsive'] ?? null) ? $config['responsive'] : [];
        $qualityConfig = is_array($config['quality'] ?? null) ? $config['quality'] : [];
        $defaultsConfig = is_array($config['defaults'] ?? null) ? $config['defaults'] : [];

        $this->driverName = $this->resolveDriverName($config['driver'] ?? self::DEFAULT_DRIVER);
        $driver = $this->driverName === 'imagick' ? new ImagickDriver : new GdDriver;
        $this->manager = new ImageManager($driver);

        $this->publicPath = public_path();
        $this->outputDir = $this->normalizeOutputDir($config['output_dir'] ?? self::DEFAULT_OUTPUT_DIR);
        $this->sizeFactors = $this->normalizeSizeFactors($responsiveConfig['size_factors'] ?? self::SIZE_FACTORS);
        $this->minWidth = $this->normalizeMinWidth($responsiveConfig['min_width'] ?? self::DEFAULT_MIN_WIDTH);
        $this->quality = $this->normalizeQualityMap($qualityConfig);
        $this->defaultFormat = $this->normalizeFormat($defaultsConfig['format'] ?? self::DEFAULT_FORMAT, self::DEFAULT_FORMAT);
        $this->defaultPictureFormats = $this->normalizeFormatsList($defaultsConfig['picture_formats'] ?? self::DEFAULT_PICTURE_FORMATS, self::DEFAULT_PICTURE_FORMATS);
        $this->defaultFallbackFormat = $this->normalizeFormat($defaultsConfig['fallback_format'] ?? self::DEFAULT_FALLBACK_FORMAT, self::DEFAULT_FALLBACK_FORMAT);
        $this->defaultLoading = $this->normalizeLoading($defaultsConfig['loading'] ?? self::DEFAULT_LOADING);
        $this->defaultFetchpriority = $this->normalizeFetchpriority($defaultsConfig['fetchpriority'] ?? self::DEFAULT_FETCHPRIORITY);
        $this->defaultSizes = $this->normalizeSizes($defaultsConfig['sizes'] ?? self::DEFAULT_SIZES);
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
                if (! class_exists(\Imagick::class)) {
                    return false;
                }

                return in_array('AVIF', \Imagick::queryFormats('AVIF'), true);
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
        ?string $format = null,
        ?string $loading = null,
        ?string $fetchpriority = null,
        ?string $id = null,
        bool $original = false,
        array $attributes = [],
    ): string {
        $sourcePath = base_path($src);
        $resolvedFormat = $this->safeFormat($this->normalizeFormat($format, $this->defaultFormat));
        $resolvedLoading = $this->normalizeLoading($loading ?? $this->defaultLoading);
        $resolvedFetchpriority = $this->normalizeFetchpriority($fetchpriority ?? $this->defaultFetchpriority);

        if (! File::exists($sourcePath)) {
            return $this->error($src);
        }

        [$width, $height] = $this->resolveDimensions($sourcePath, $width, $height);

        // Original? → simply copy to public, no processing
        if ($original) {
            $url = $this->copyOriginal($sourcePath);

            return $this->buildSimpleImgTag($url, $alt, $width, $height, $class, $resolvedLoading, $resolvedFetchpriority, $id, $attributes);
        }

        if ($this->shouldBypassOptimization($sourcePath, $width, $height)) {
            $url = $this->copyOriginal($sourcePath);
            $attributes = $this->withFallbackMetadata($attributes, 'memory-limit');

            return $this->buildSimpleImgTag($url, $alt, $width, $height, $class, $resolvedLoading, $resolvedFetchpriority, $id, $attributes);
        }

        $sourceModified = File::lastModified($sourcePath);

        // Generate ONLY ONE variant (exactly the desired width)
        try {
            $variants = $this->getOrCreateVariants($sourcePath, $sourceModified, $width, $resolvedFormat, singleOnly: true);
        } catch (Throwable) {
            $url = $this->copyOriginal($sourcePath);
            $attributes = $this->withFallbackMetadata($attributes, 'optimization-error');

            return $this->buildSimpleImgTag($url, $alt, $width, $height, $class, $resolvedLoading, $resolvedFetchpriority, $id, $attributes);
        }

        if (empty($variants)) {
            return $this->error($src);
        }

        $variant = $this->findClosestVariant($variants, $width ?? $variants[0]['width']);

        // Calculate height if not specified
        if ($height === null && $width !== null && $variant) {
            $height = (int) round($width * ($variant['height'] / $variant['width']));
        }

        return $this->buildSimpleImgTag($variant['url'], $alt, $width, $height, $class, $resolvedLoading, $resolvedFetchpriority, $id, $attributes);
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
        ?string $format = null,
        ?string $loading = null,
        ?string $fetchpriority = null,
        ?string $sizes = null,
        ?string $id = null,
        bool $original = false,
        array $attributes = [],
    ): string {
        $sourcePath = base_path($src);
        $resolvedFormat = $this->safeFormat($this->normalizeFormat($format, $this->defaultFormat));
        $resolvedLoading = $this->normalizeLoading($loading ?? $this->defaultLoading);
        $resolvedFetchpriority = $this->normalizeFetchpriority($fetchpriority ?? $this->defaultFetchpriority);
        $resolvedSizes = $this->normalizeSizes($sizes ?? $this->defaultSizes);

        if (! File::exists($sourcePath)) {
            return $this->error($src);
        }

        [$width, $height] = $this->resolveDimensions($sourcePath, $width, $height);

        if ($original) {
            $url = $this->copyOriginal($sourcePath);

            return $this->buildSimpleImgTag($url, $alt, $width, $height, $class, $resolvedLoading, $resolvedFetchpriority, $id, $attributes);
        }

        if ($this->shouldBypassOptimization($sourcePath, $width, $height)) {
            $url = $this->copyOriginal($sourcePath);
            $attributes = $this->withFallbackMetadata($attributes, 'memory-limit');

            return $this->buildSimpleImgTag($url, $alt, $width, $height, $class, $resolvedLoading, $resolvedFetchpriority, $id, $attributes);
        }

        $sourceModified = File::lastModified($sourcePath);
        try {
            $variants = $this->getOrCreateVariants($sourcePath, $sourceModified, $width, $resolvedFormat);
        } catch (Throwable) {
            $url = $this->copyOriginal($sourcePath);
            $attributes = $this->withFallbackMetadata($attributes, 'optimization-error');

            return $this->buildSimpleImgTag($url, $alt, $width, $height, $class, $resolvedLoading, $resolvedFetchpriority, $id, $attributes);
        }

        if (empty($variants)) {
            return $this->error($src);
        }

        return $this->buildResponsiveImgTag($variants, $alt, $width, $height, $class, $resolvedLoading, $resolvedFetchpriority, $resolvedSizes, $id, $attributes);
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
        ?array $formats = null,
        ?string $fallbackFormat = null,
        ?string $loading = null,
        ?string $fetchpriority = null,
        ?string $sizes = null,
        ?string $id = null,
        bool $original = false,
        array $attributes = [],
    ): string {
        $resolvedFetchpriority = $this->normalizeFetchpriority($fetchpriority ?? $this->defaultFetchpriority);
        $resolvedLoading = $this->normalizeLoading($loading ?? $this->defaultLoading);
        $resolvedSizes = $this->normalizeSizes($sizes ?? $this->defaultSizes);
        $resolvedFormats = $this->normalizeFormatsList($formats, $this->defaultPictureFormats);
        $resolvedFallbackFormat = $this->safeFormat($this->normalizeFormat($fallbackFormat, $this->defaultFallbackFormat));

        if ($resolvedFetchpriority === 'high' && $resolvedLoading === 'lazy') {
            $resolvedLoading = 'eager';
        }

        $sourcePath = base_path($src);

        if (! File::exists($sourcePath)) {
            return $this->error($src);
        }

        [$width, $height] = $this->resolveDimensions($sourcePath, $width, $height);

        // Original - no <picture> needed, simple <img>
        if ($original) {
            $url = $this->copyOriginal($sourcePath);

            return $this->buildSimpleImgTag($url, $alt, $width, $height, $class, $resolvedLoading, $resolvedFetchpriority, $id, $attributes);
        }

        if ($this->shouldBypassOptimization($sourcePath, $width, $height)) {
            $url = $this->copyOriginal($sourcePath);
            $attributes = $this->withFallbackMetadata($attributes, 'memory-limit');

            return $this->buildSimpleImgTag($url, $alt, $width, $height, $imgClass ?: $class, $resolvedLoading, $resolvedFetchpriority, $id, $attributes);
        }

        $sourceModified = File::lastModified($sourcePath);

        // Variants per format - skip unsupported formats
        $formatVariants = [];
        foreach ($resolvedFormats as $fmt) {
            $fmt = strtolower($fmt);
            if (! $this->supportsFormat($fmt)) {
                continue;
            }
            try {
                $variants = $this->getOrCreateVariants($sourcePath, $sourceModified, $width, $fmt);
            } catch (Throwable) {
                continue;
            }
            if (! empty($variants)) {
                $formatVariants[$fmt] = $variants;
            }
        }

        // Fallback (e.g. jpg for old browsers)
        try {
            $fallbackVariants = $this->getOrCreateVariants($sourcePath, $sourceModified, $width, $resolvedFallbackFormat);
        } catch (Throwable) {
            $url = $this->copyOriginal($sourcePath);
            $attributes = $this->withFallbackMetadata($attributes, 'optimization-error');

            return $this->buildSimpleImgTag($url, $alt, $width, $height, $imgClass ?: $class, $resolvedLoading, $resolvedFetchpriority, $id, $attributes);
        }

        if (empty($fallbackVariants) && empty($formatVariants)) {
            return $this->error($src);
        }

        return $this->buildPictureTag(
            $formatVariants, $fallbackVariants, $resolvedFallbackFormat,
            $alt, $width, $height, $class, $imgClass, $sourceClass, $resolvedLoading, $resolvedFetchpriority, $resolvedSizes, $id, $attributes,
        );
    }

    /**
     * img_url() - Return only the URL.
     */
    public function url(
        string $src,
        ?int $width = null,
        ?string $format = null,
        bool $original = false,
    ): string {
        $sourcePath = base_path($src);

        if (! File::exists($sourcePath)) {
            return '';
        }

        if ($original) {
            return $this->copyOriginal($sourcePath);
        }

        [$resolvedWidth, $resolvedHeight] = $this->resolveDimensions($sourcePath, $width, null);
        if ($this->shouldBypassOptimization($sourcePath, $resolvedWidth, $resolvedHeight)) {
            return $this->copyOriginal($sourcePath);
        }

        $sourceModified = File::lastModified($sourcePath);
        $resolvedFormat = $this->safeFormat($this->normalizeFormat($format, $this->defaultFormat));
        try {
            $variants = $this->getOrCreateVariants($sourcePath, $sourceModified, $resolvedWidth, $resolvedFormat, singleOnly: true);
        } catch (Throwable) {
            return $this->copyOriginal($sourcePath);
        }

        if (empty($variants)) {
            return '';
        }

        $targetWidth = $resolvedWidth ?? $variants[0]['width'];

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
        $quality = $this->quality[$format] ?? (self::QUALITY[$format] ?? 80);

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

        foreach ($this->sizeFactors as $factor) {
            $w = (int) round($baseWidth * $factor);

            if ($w > $originalWidth || $w < $this->minWidth) {
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

    /**
     * Resolve requested dimensions while preserving original aspect ratio.
     */
    protected function resolveDimensions(string $sourcePath, ?int $width, ?int $height): array
    {
        $width = $this->normalizeDimension($width);
        $height = $this->normalizeDimension($height);
        $originalDimensions = $this->readImageDimensions($sourcePath);

        if ($originalDimensions === null) {
            return [$width, $height];
        }

        [$originalWidth, $originalHeight] = $originalDimensions;

        if ($width === null && $height === null) {
            return [$originalWidth, $originalHeight];
        }

        if ($width === null && $height !== null) {
            $width = max(1, (int) round($height * ($originalWidth / $originalHeight)));
        }

        if ($height === null && $width !== null) {
            $height = max(1, (int) round($width * ($originalHeight / $originalWidth)));
        }

        return [$width, $height];
    }

    protected function normalizeDimension(?int $dimension): ?int
    {
        if ($dimension === null) {
            return null;
        }

        return $dimension > 0 ? $dimension : null;
    }

    protected function readImageDimensions(string $sourcePath): ?array
    {
        $dimensions = @getimagesize($sourcePath);

        if (! is_array($dimensions) || empty($dimensions[0]) || empty($dimensions[1])) {
            return null;
        }

        $width = (int) $dimensions[0];
        $height = (int) $dimensions[1];

        if ($width <= 0 || $height <= 0) {
            return null;
        }

        return [$width, $height];
    }

    protected function withFallbackMetadata(array $attributes, string $reason): array
    {
        if (! array_key_exists('data-image-optimizer-status', $attributes)) {
            $attributes['data-image-optimizer-status'] = 'original-fallback';
        }

        if (! array_key_exists('data-image-optimizer-reason', $attributes)) {
            $attributes['data-image-optimizer-reason'] = $reason;
        }

        return $attributes;
    }

    protected function shouldBypassOptimization(string $sourcePath, ?int $targetWidth, ?int $targetHeight): bool
    {
        if ($this->driverName !== 'gd') {
            return false;
        }

        $memoryLimit = $this->memoryLimitInBytes();
        if ($memoryLimit === null) {
            return false;
        }

        $originalDimensions = $this->readImageDimensions($sourcePath);
        if ($originalDimensions === null) {
            return false;
        }

        [$sourceWidth, $sourceHeight] = $originalDimensions;
        $targetWidth ??= $sourceWidth;
        $targetHeight ??= $sourceHeight;

        if ($targetWidth <= 0 || $targetHeight <= 0) {
            return false;
        }

        $estimatedBytes = $this->estimateGdProcessingBytes($sourceWidth, $sourceHeight, $targetWidth, $targetHeight);
        $availableBytes = $memoryLimit - memory_get_usage(true);

        // Keep a 15% headroom to avoid fatal OOM in GD operations.
        return $availableBytes > 0 && $estimatedBytes > (int) floor($availableBytes * 0.85);
    }

    protected function estimateGdProcessingBytes(
        int $sourceWidth,
        int $sourceHeight,
        int $targetWidth,
        int $targetHeight,
    ): int {
        $bytesPerPixel = 4;

        $sourceBuffer = $sourceWidth * $sourceHeight * $bytesPerPixel * 2;
        $targetBuffer = $targetWidth * $targetHeight * $bytesPerPixel * 2;
        $overhead = 32 * 1024 * 1024;

        return $sourceBuffer + $targetBuffer + $overhead;
    }

    protected function memoryLimitInBytes(): ?int
    {
        $limit = ini_get('memory_limit');
        if (! is_string($limit) || $limit === '' || $limit === '-1') {
            return null;
        }

        $limit = trim($limit);
        $unit = strtolower(substr($limit, -1));
        $value = (float) $limit;

        if ($value <= 0) {
            return null;
        }

        return match ($unit) {
            'g' => (int) ($value * 1024 * 1024 * 1024),
            'm' => (int) ($value * 1024 * 1024),
            'k' => (int) ($value * 1024),
            default => (int) $value,
        };
    }

    protected function resolveDriverName(mixed $configuredDriver): string
    {
        $requested = is_string($configuredDriver) ? strtolower(trim($configuredDriver)) : self::DEFAULT_DRIVER;
        if (! in_array($requested, self::ALLOWED_DRIVERS, true)) {
            $requested = self::DEFAULT_DRIVER;
        }

        $hasImagick = extension_loaded('imagick');
        $hasGd = extension_loaded('gd');

        return match ($requested) {
            'imagick' => $hasImagick ? 'imagick' : 'gd',
            'gd' => $hasGd ? 'gd' : ($hasImagick ? 'imagick' : 'gd'),
            default => $hasImagick ? 'imagick' : 'gd',
        };
    }

    protected function normalizeOutputDir(mixed $outputDir): string
    {
        if (! is_string($outputDir)) {
            return self::DEFAULT_OUTPUT_DIR;
        }

        $normalized = trim(str_replace('\\', '/', $outputDir));
        $normalized = preg_replace('#/+#', '/', $normalized) ?? '';
        $normalized = trim($normalized, '/');

        if ($normalized === '' || str_contains($normalized, '..')) {
            return self::DEFAULT_OUTPUT_DIR;
        }

        return $normalized;
    }

    protected function normalizeSizeFactors(mixed $sizeFactors): array
    {
        if (! is_array($sizeFactors)) {
            return self::SIZE_FACTORS;
        }

        $normalized = [];

        foreach ($sizeFactors as $factor) {
            if (! is_numeric($factor)) {
                continue;
            }

            $factor = (float) $factor;
            if ($factor <= 0) {
                continue;
            }

            $normalized[] = $factor;
        }

        $normalized = array_values(array_unique($normalized));
        sort($normalized);

        return $normalized === [] ? self::SIZE_FACTORS : $normalized;
    }

    protected function normalizeMinWidth(mixed $minWidth): int
    {
        if (! is_numeric($minWidth)) {
            return self::DEFAULT_MIN_WIDTH;
        }

        $normalized = (int) $minWidth;

        return $normalized > 0 ? $normalized : self::DEFAULT_MIN_WIDTH;
    }

    protected function normalizeQualityMap(mixed $qualityConfig): array
    {
        $quality = self::QUALITY;

        if (! is_array($qualityConfig)) {
            return $quality;
        }

        foreach ($quality as $format => $defaultQuality) {
            $rawValue = $qualityConfig[$format] ?? null;
            if (! is_numeric($rawValue)) {
                continue;
            }

            $quality[$format] = max(1, min(100, (int) $rawValue));
        }

        if (isset($qualityConfig['jpg']) && ! isset($qualityConfig['jpeg'])) {
            $quality['jpeg'] = $quality['jpg'];
        }
        if (isset($qualityConfig['jpeg']) && ! isset($qualityConfig['jpg'])) {
            $quality['jpg'] = $quality['jpeg'];
        }

        return $quality;
    }

    protected function normalizeFormatsList(mixed $formats, array $fallback): array
    {
        $fallbackList = $this->sanitizeFormatsArray($fallback);
        if ($fallbackList === []) {
            $fallbackList = self::DEFAULT_PICTURE_FORMATS;
        }

        if (! is_array($formats)) {
            return $fallbackList;
        }

        $normalized = $this->sanitizeFormatsArray($formats);

        return $normalized === [] ? $fallbackList : $normalized;
    }

    protected function sanitizeFormatsArray(array $formats): array
    {
        $normalized = [];

        foreach ($formats as $format) {
            if (! is_string($format)) {
                continue;
            }

            $format = strtolower(trim($format));
            if (! in_array($format, self::ALLOWED_FORMATS, true)) {
                continue;
            }

            $normalized[] = $format;
        }

        return array_values(array_unique($normalized));
    }

    protected function normalizeFormat(mixed $format, string $fallback): string
    {
        $fallback = strtolower(trim($fallback));
        if (! in_array($fallback, self::ALLOWED_FORMATS, true)) {
            $fallback = self::DEFAULT_FORMAT;
        }

        if (! is_string($format)) {
            return $fallback;
        }

        $normalized = strtolower(trim($format));

        return in_array($normalized, self::ALLOWED_FORMATS, true) ? $normalized : $fallback;
    }

    protected function normalizeLoading(mixed $loading): string
    {
        if (! is_string($loading)) {
            return self::DEFAULT_LOADING;
        }

        $normalized = strtolower(trim($loading));

        return in_array($normalized, self::ALLOWED_LOADING, true) ? $normalized : self::DEFAULT_LOADING;
    }

    protected function normalizeFetchpriority(mixed $fetchpriority): string
    {
        if (! is_string($fetchpriority)) {
            return self::DEFAULT_FETCHPRIORITY;
        }

        $normalized = strtolower(trim($fetchpriority));

        return in_array($normalized, self::ALLOWED_FETCHPRIORITY, true) ? $normalized : self::DEFAULT_FETCHPRIORITY;
    }

    protected function normalizeSizes(mixed $sizes): string
    {
        if (! is_string($sizes)) {
            return self::DEFAULT_SIZES;
        }

        $normalized = trim($sizes);

        return $normalized !== '' ? $normalized : self::DEFAULT_SIZES;
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
                    $manifest['format'] ?? $this->defaultFormat,
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
