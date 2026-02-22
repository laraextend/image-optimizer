<?php

namespace Laraextend\MediaToolkit\Builders;

use Illuminate\Support\Facades\File;
use Laraextend\MediaToolkit\Cache\ManifestCache;
use Laraextend\MediaToolkit\DTOs\CropOptions;
use Laraextend\MediaToolkit\DTOs\ResizeOptions;
use Laraextend\MediaToolkit\DTOs\WatermarkOptions;
use Laraextend\MediaToolkit\Enums\ImageMode;
use Laraextend\MediaToolkit\Enums\ImageOutputType;
use Laraextend\MediaToolkit\Enums\WatermarkPosition;
use Laraextend\MediaToolkit\Exceptions\MediaBuilderException;
use Laraextend\MediaToolkit\Operations\Image\CropOperation;
use Laraextend\MediaToolkit\Operations\Image\FilterOperation;
use Laraextend\MediaToolkit\Operations\Image\FitOperation;
use Laraextend\MediaToolkit\Operations\Image\ImageOperationInterface;
use Laraextend\MediaToolkit\Operations\Image\ResizeOperation;
use Laraextend\MediaToolkit\Operations\Image\StretchOperation;
use Laraextend\MediaToolkit\Operations\Image\WatermarkOperation;
use Laraextend\MediaToolkit\Processing\ImageProcessor;
use Laraextend\MediaToolkit\Rendering\ImageHtmlRenderer;
use Throwable;

class ImageBuilder extends BaseBuilder
{
    // ── Output mode ────────────────────────────────────────────────────────
    protected ImageOutputType $outputType          = ImageOutputType::Img;
    protected ?string         $responsiveSizes     = null;
    protected ?array          $pictureFormats      = null;
    protected ?string         $pictureFallback     = null;
    protected string          $pictureImgClass     = '';
    protected string          $pictureSourceClass  = '';
    protected array           $pictureExtraAttrs   = [];

    // ── Size operation tracking (mutually exclusive) ────────────────────────
    protected ?string $sizeOp       = null;  // 'resize' | 'stretch' | 'fit' | 'crop'
    protected bool    $allowUpscale = false;

    // ── Operations pipeline ────────────────────────────────────────────────
    /** @var ImageOperationInterface[] */
    protected array $operations = [];

    public function __construct(
        protected readonly string            $path,
        protected readonly ImageProcessor    $processor,
        protected readonly ManifestCache     $cache,
        protected readonly ImageHtmlRenderer $renderer,
    ) {}

    // ─────────────────────────────────────────────────────────────
    //  SIZE & CROP METHODS
    // ─────────────────────────────────────────────────────────────

    /**
     * Proportional resize by width, height, or both (contain-box).
     * At least one dimension must be provided.
     * Upscaling beyond the original size is silently capped unless ->upscale() is chained.
     *
     * resize(width: 800)               → scale to 800px wide, height proportional
     * resize(height: 600)              → scale to 600px tall, width proportional
     * resize(width: 800, height: 600)  → fit inside 800×600 box, keep ratio, no crop
     */
    public function resize(?int $width = null, ?int $height = null): static
    {
        $this->assertTransformationsAllowed('resize');
        $this->assertNoSizeOp('resize');

        if ($width === null && $height === null) {
            throw new MediaBuilderException('resize() requires at least width or height.');
        }

        $this->sizeOp     = 'resize';
        $this->operations[] = new ResizeOperation(new ResizeOptions($width, $height, false));

        return $this;
    }

    /**
     * Allow the image to be scaled beyond its original dimensions.
     * Must be chained directly after resize().
     */
    public function upscale(): static
    {
        $this->assertTransformationsAllowed('upscale');

        if ($this->sizeOp !== 'resize') {
            throw new MediaBuilderException('upscale() must be called directly after resize().');
        }

        $this->allowUpscale = true;

        // Update the last ResizeOperation to reflect the allowUpscale flag.
        foreach (array_reverse(array_keys($this->operations)) as $i) {
            if ($this->operations[$i] instanceof ResizeOperation) {
                $old = $this->operations[$i]->options;
                $this->operations[$i] = new ResizeOperation(
                    new ResizeOptions($old->width, $old->height, true)
                );
                break;
            }
        }

        return $this;
    }

    /**
     * Stretch to exact dimensions — aspect ratio is ignored.
     * Both dimensions are required.
     */
    public function stretch(int $width, int $height): static
    {
        $this->assertTransformationsAllowed('stretch');
        $this->assertNoSizeOp('stretch');

        $this->sizeOp       = 'stretch';
        $this->operations[] = new StretchOperation($width, $height);

        return $this;
    }

    /**
     * Cover + crop: fills the given frame completely.
     * The image is scaled so the shorter side fills the frame, overflow is cropped from center.
     * Both dimensions are required.
     *
     * Example: 640×480 → fit(400, 100) → scale to 400×300 → crop height to 400×100
     */
    public function fit(int $width, int $height): static
    {
        $this->assertTransformationsAllowed('fit');
        $this->assertNoSizeOp('fit');

        $this->sizeOp       = 'fit';
        $this->operations[] = new FitOperation($width, $height);

        return $this;
    }

    /**
     * Extract a region from the original image without scaling.
     *
     * @param int         $width   Width of the extracted region in pixels
     * @param int         $height  Height of the extracted region in pixels
     * @param int|string  $x       Horizontal offset: pixel int | 'left' | 'center' | 'right'
     * @param int|string  $y       Vertical offset:   pixel int | 'top'  | 'center' | 'bottom'
     */
    public function crop(int $width, int $height, int|string $x = 0, int|string $y = 0): static
    {
        $this->assertTransformationsAllowed('crop');
        $this->assertNoSizeOp('crop');

        $this->sizeOp       = 'crop';
        $this->operations[] = new CropOperation(new CropOptions($width, $height, $x, $y));

        return $this;
    }

    // ─────────────────────────────────────────────────────────────
    //  FILTER METHODS (stackable)
    // ─────────────────────────────────────────────────────────────

    public function grayscale(): static
    {
        return $this->addFilter('grayscale');
    }

    public function sepia(): static
    {
        return $this->addFilter('sepia');
    }

    public function negate(): static
    {
        return $this->addFilter('negate');
    }

    /**
     * @param int $level  -255 (darkest) to +255 (brightest)
     */
    public function brightness(int $level): static
    {
        return $this->addFilter('brightness', [max(-255, min(255, $level))]);
    }

    /**
     * @param int $level  -100 (more contrast) to +100 (less contrast)
     */
    public function contrast(int $level): static
    {
        return $this->addFilter('contrast', [max(-100, min(100, $level))]);
    }

    /**
     * @param int $r  -255 to +255
     * @param int $g  -255 to +255
     * @param int $b  -255 to +255
     */
    public function colorize(int $r, int $g, int $b): static
    {
        return $this->addFilter('colorize', [
            max(-255, min(255, $r)),
            max(-255, min(255, $g)),
            max(-255, min(255, $b)),
        ]);
    }

    /**
     * @param int $amount  Number of times the blur filter is applied (default: 1)
     */
    public function blur(int $amount = 1): static
    {
        return $this->addFilter('blur', [max(1, $amount)]);
    }

    /**
     * @param int $level  -10 (maximum sharpen) to +10 (maximum smooth)
     */
    public function smooth(int $level): static
    {
        return $this->addFilter('smooth', [max(-10, min(10, $level))]);
    }

    /**
     * @param int|string $angle  Degrees counter-clockwise, or 'auto' for EXIF-based rotation
     */
    public function rotate(int|string $angle): static
    {
        return $this->addFilter('rotate', [$angle]);
    }

    public function flipHorizontal(): static
    {
        return $this->addFilter('flipH');
    }

    public function flipVertical(): static
    {
        return $this->addFilter('flipV');
    }

    public function flipBoth(): static
    {
        return $this->addFilter('flipBoth');
    }

    // ─────────────────────────────────────────────────────────────
    //  WATERMARK
    // ─────────────────────────────────────────────────────────────

    /**
     * Overlay a watermark image.
     *
     * @param string $source    Path to watermark file (relative to base_path())
     * @param string $position  'top-left'|'top-center'|'top-right'|'center-left'|'center'|
     *                          'center-right'|'bottom-left'|'bottom-center'|'bottom-right'
     * @param int    $padding   Distance from edge in pixels (default: 10)
     * @param int    $opacity   1–100 (default: 100)
     */
    public function watermark(
        string $source,
        string $position = 'bottom-right',
        int    $padding  = 10,
        int    $opacity  = 100,
    ): static {
        $this->assertTransformationsAllowed('watermark');

        $positionEnum   = WatermarkPosition::from($position);
        $absolutePath   = base_path($source);
        $this->operations[] = new WatermarkOperation(
            new WatermarkOptions($source, $positionEnum, $padding, max(1, min(100, $opacity))),
            $absolutePath,
        );

        return $this;
    }

    // ─────────────────────────────────────────────────────────────
    //  OUTPUT MODE CONFIGURATION
    // ─────────────────────────────────────────────────────────────

    /**
     * Switch output to responsive <img> with srcset.
     *
     * @param string|null $sizes  CSS sizes attribute (null = from config, default '100vw')
     */
    public function responsive(?string $sizes = null): static
    {
        $this->outputType      = ImageOutputType::Responsive;
        $this->responsiveSizes = $sizes;

        return $this;
    }

    /**
     * Switch output to <picture> with <source> elements and <img> fallback.
     *
     * @param array|null  $formats      Modern formats for <source> (null = from config)
     * @param string|null $fallback     Format for <img> fallback (null = from config)
     * @param string      $imgClass     CSS class(es) for the inner <img> element
     * @param string      $sourceClass  CSS class(es) for all <source> elements
     */
    public function picture(
        ?array  $formats     = null,
        ?string $fallback    = null,
        string  $imgClass    = '',
        string  $sourceClass = '',
    ): static {
        $this->outputType          = ImageOutputType::Picture;
        $this->pictureFormats      = $formats;
        $this->pictureFallback     = $fallback;
        $this->pictureImgClass     = $imgClass;
        $this->pictureSourceClass  = $sourceClass;

        return $this;
    }

    /**
     * Extra HTML attributes forwarded directly to the <picture> outer element.
     * Use this when you need to place wire:key, x-bind, or data-* on the <picture> tag.
     *
     * @param array $attrs  Associative attribute array, e.g. ['wire:key' => 'hero', 'data-x' => '1']
     */
    public function pictureAttributes(array $attrs): static
    {
        $this->pictureExtraAttrs = $attrs;

        return $this;
    }

    // ─────────────────────────────────────────────────────────────
    //  OUTPUT METHODS
    // ─────────────────────────────────────────────────────────────

    /**
     * Returns the URL of the processed (or original) image.
     */
    public function url(): string
    {
        $sourcePath = base_path($this->path);

        if (! File::exists($sourcePath)) {
            return '';
        }

        if ($this->mode === ImageMode::Original) {
            return $this->cache->copyOriginal($sourcePath);
        }

        $format      = $this->processor->safeFormat($this->resolveFormat());
        $quality     = $this->resolveQuality($format);
        $fingerprint = $this->buildOperationsFingerprint();
        $dimensions  = $this->resolveDimensionsFromSource($sourcePath);

        [$resolvedWidth, $resolvedHeight] = $dimensions;

        if ($this->processor->shouldBypassOptimization($sourcePath, $resolvedWidth, $resolvedHeight)) {
            return $this->cache->copyOriginal($sourcePath);
        }

        $sourceModified = File::lastModified($sourcePath);

        try {
            $variants = $this->cache->getOrCreate(
                sourcePath:             $sourcePath,
                sourceModified:         $sourceModified,
                displayWidth:           $resolvedWidth,
                format:                 $format,
                singleOnly:             true,
                operations:             $this->operations,
                operationsFingerprint:  $fingerprint,
                quality:                $quality,
                noCache:                $this->disableCache,
            );
        } catch (Throwable) {
            return $this->cache->copyOriginal($sourcePath);
        }

        if (empty($variants)) {
            return '';
        }

        $targetWidth = $resolvedWidth ?? $variants[0]['width'];

        return $this->renderer->findClosestVariant($variants, $targetWidth)['url'] ?? '';
    }

    /**
     * Returns the HTML output.
     * Rendered element depends on the active output mode:
     *   (default)         → <img src="..." ...>
     *   ->responsive(...) → <img src="..." srcset="..." sizes="...">
     *   ->picture(...)    → <picture><source .../><img .../></picture>
     */
    public function html(
        string  $alt        = '',
        string  $class      = '',
        ?string $id         = null,
        array   $attributes = [],
    ): string {
        return match ($this->outputType) {
            ImageOutputType::Responsive => $this->renderResponsive($alt, $class, $id, $attributes),
            ImageOutputType::Picture    => $this->renderPicture($alt, $class, $id, $attributes),
            default                     => $this->renderSingle($alt, $class, $id, $attributes),
        };
    }

    // ─────────────────────────────────────────────────────────────
    //  RENDER IMPLEMENTATIONS
    // ─────────────────────────────────────────────────────────────

    protected function renderSingle(string $alt, string $class, ?string $id, array $attributes): string
    {
        $sourcePath = base_path($this->path);

        if (! File::exists($sourcePath)) {
            return $this->renderer->error($this->path);
        }

        $resolvedLoading      = $this->resolveLoading();
        $resolvedFetchpriority = $this->resolveFetchpriority();

        if ($this->mode === ImageMode::Original) {
            $url = $this->cache->copyOriginal($sourcePath);
            [$w, $h] = $this->resolveDimensionsFromSource($sourcePath);

            return $this->renderer->buildSimpleImgTag($url, $alt, $w, $h, $class, $resolvedLoading, $resolvedFetchpriority, $id, $attributes);
        }

        $format      = $this->processor->safeFormat($this->resolveFormat());
        $quality     = $this->resolveQuality($format);
        $fingerprint = $this->buildOperationsFingerprint();
        [$resolvedWidth, $resolvedHeight] = $this->resolveDimensionsFromSource($sourcePath);

        if ($this->processor->shouldBypassOptimization($sourcePath, $resolvedWidth, $resolvedHeight)) {
            $url        = $this->cache->copyOriginal($sourcePath);
            $attributes = $this->renderer->withFallbackMetadata($attributes, 'memory-limit');

            return $this->renderer->buildSimpleImgTag($url, $alt, $resolvedWidth, $resolvedHeight, $class, $resolvedLoading, $resolvedFetchpriority, $id, $attributes);
        }

        $sourceModified = File::lastModified($sourcePath);

        try {
            $variants = $this->cache->getOrCreate(
                sourcePath:             $sourcePath,
                sourceModified:         $sourceModified,
                displayWidth:           $resolvedWidth,
                format:                 $format,
                singleOnly:             true,
                operations:             $this->operations,
                operationsFingerprint:  $fingerprint,
                quality:                $quality,
                noCache:                $this->disableCache,
            );
        } catch (Throwable) {
            $url        = $this->cache->copyOriginal($sourcePath);
            $attributes = $this->renderer->withFallbackMetadata($attributes, 'optimization-error');

            return $this->renderer->buildSimpleImgTag($url, $alt, $resolvedWidth, $resolvedHeight, $class, $resolvedLoading, $resolvedFetchpriority, $id, $attributes);
        }

        if (empty($variants)) {
            return $this->renderer->error($this->path);
        }

        $variant = $this->renderer->findClosestVariant($variants, $resolvedWidth ?? $variants[0]['width']);
        $height  = ($resolvedHeight === null && $resolvedWidth !== null && $variant)
            ? (int) round($resolvedWidth * ($variant['height'] / $variant['width']))
            : $resolvedHeight;

        return $this->renderer->buildSimpleImgTag($variant['url'], $alt, $resolvedWidth, $height, $class, $resolvedLoading, $resolvedFetchpriority, $id, $attributes);
    }

    protected function renderResponsive(string $alt, string $class, ?string $id, array $attributes): string
    {
        $sourcePath = base_path($this->path);

        if (! File::exists($sourcePath)) {
            return $this->renderer->error($this->path);
        }

        $resolvedLoading       = $this->resolveLoading();
        $resolvedFetchpriority = $this->resolveFetchpriority();
        $resolvedSizes         = $this->resolveSizes($this->responsiveSizes);
        $format                = $this->processor->safeFormat($this->resolveFormat());
        $quality               = $this->resolveQuality($format);
        $fingerprint           = $this->buildOperationsFingerprint();
        [$resolvedWidth, $resolvedHeight] = $this->resolveDimensionsFromSource($sourcePath);

        if ($this->processor->shouldBypassOptimization($sourcePath, $resolvedWidth, $resolvedHeight)) {
            $url        = $this->cache->copyOriginal($sourcePath);
            $attributes = $this->renderer->withFallbackMetadata($attributes, 'memory-limit');

            return $this->renderer->buildSimpleImgTag($url, $alt, $resolvedWidth, $resolvedHeight, $class, $resolvedLoading, $resolvedFetchpriority, $id, $attributes);
        }

        $sourceModified = File::lastModified($sourcePath);

        try {
            $variants = $this->cache->getOrCreate(
                sourcePath:             $sourcePath,
                sourceModified:         $sourceModified,
                displayWidth:           $resolvedWidth,
                format:                 $format,
                singleOnly:             false,
                operations:             $this->operations,
                operationsFingerprint:  $fingerprint,
                quality:                $quality,
                noCache:                $this->disableCache,
            );
        } catch (Throwable) {
            $url        = $this->cache->copyOriginal($sourcePath);
            $attributes = $this->renderer->withFallbackMetadata($attributes, 'optimization-error');

            return $this->renderer->buildSimpleImgTag($url, $alt, $resolvedWidth, $resolvedHeight, $class, $resolvedLoading, $resolvedFetchpriority, $id, $attributes);
        }

        if (empty($variants)) {
            return $this->renderer->error($this->path);
        }

        return $this->renderer->buildResponsiveImgTag($variants, $alt, $resolvedWidth, $resolvedHeight, $class, $resolvedLoading, $resolvedFetchpriority, $resolvedSizes, $id, $attributes);
    }

    protected function renderPicture(string $alt, string $class, ?string $id, array $attributes): string
    {
        $sourcePath = base_path($this->path);

        if (! File::exists($sourcePath)) {
            return $this->renderer->error($this->path);
        }

        $resolvedLoading       = $this->resolveLoading();
        $resolvedFetchpriority = $this->resolveFetchpriority();
        $resolvedSizes         = $this->resolveSizes($this->responsiveSizes);
        $quality               = $this->resolveQuality($this->resolveFormat());
        $fingerprint           = $this->buildOperationsFingerprint();
        [$resolvedWidth, $resolvedHeight] = $this->resolveDimensionsFromSource($sourcePath);

        $defaultPictureFormats = $this->normalizeFormatsList(
            config('media-toolkit.image.defaults.picture_formats', ['avif', 'webp']),
            ['avif', 'webp'],
        );
        $defaultFallbackFormat = $this->normalizeFormat(
            config('media-toolkit.image.defaults.fallback_format', 'jpg'),
            'jpg',
        );

        $resolvedFormats  = $this->normalizeFormatsList($this->pictureFormats, $defaultPictureFormats);
        $resolvedFallback = $this->processor->safeFormat(
            $this->normalizeFormat($this->pictureFallback, $defaultFallbackFormat)
        );

        if ($this->processor->shouldBypassOptimization($sourcePath, $resolvedWidth, $resolvedHeight)) {
            $url        = $this->cache->copyOriginal($sourcePath);
            $attributes = $this->renderer->withFallbackMetadata($attributes, 'memory-limit');

            return $this->renderer->buildSimpleImgTag($url, $alt, $resolvedWidth, $resolvedHeight, $this->pictureImgClass ?: $class, $resolvedLoading, $resolvedFetchpriority, $id, $attributes);
        }

        $sourceModified = File::lastModified($sourcePath);

        // Generate variants per modern format
        $formatVariants = [];
        foreach ($resolvedFormats as $fmt) {
            if (! $this->processor->supportsFormat($fmt)) {
                continue;
            }
            try {
                $variants = $this->cache->getOrCreate(
                    sourcePath:             $sourcePath,
                    sourceModified:         $sourceModified,
                    displayWidth:           $resolvedWidth,
                    format:                 $fmt,
                    singleOnly:             false,
                    operations:             $this->operations,
                    operationsFingerprint:  $fingerprint,
                    quality:                $this->resolveQuality($fmt),
                    noCache:                $this->disableCache,
                );
                if (! empty($variants)) {
                    $formatVariants[$fmt] = $variants;
                }
            } catch (Throwable) {
                continue;
            }
        }

        // Generate fallback variants
        try {
            $fallbackVariants = $this->cache->getOrCreate(
                sourcePath:             $sourcePath,
                sourceModified:         $sourceModified,
                displayWidth:           $resolvedWidth,
                format:                 $resolvedFallback,
                singleOnly:             false,
                operations:             $this->operations,
                operationsFingerprint:  $fingerprint,
                quality:                $this->resolveQuality($resolvedFallback),
                noCache:                $this->disableCache,
            );
        } catch (Throwable) {
            $url        = $this->cache->copyOriginal($sourcePath);
            $attributes = $this->renderer->withFallbackMetadata($attributes, 'optimization-error');

            return $this->renderer->buildSimpleImgTag($url, $alt, $resolvedWidth, $resolvedHeight, $this->pictureImgClass ?: $class, $resolvedLoading, $resolvedFetchpriority, $id, $attributes);
        }

        if (empty($fallbackVariants) && empty($formatVariants)) {
            return $this->renderer->error($this->path);
        }

        return $this->renderer->buildPictureTag(
            $formatVariants,
            $fallbackVariants,
            $resolvedFallback,
            $alt,
            $resolvedWidth,
            $resolvedHeight,
            $class,
            $this->pictureImgClass,
            $this->pictureSourceClass,
            $resolvedLoading,
            $resolvedFetchpriority,
            $resolvedSizes,
            $id,
            $attributes,
            $this->pictureExtraAttrs,
        );
    }

    // ─────────────────────────────────────────────────────────────
    //  INTERNAL HELPERS
    // ─────────────────────────────────────────────────────────────

    /**
     * Build a stable fingerprint string from the entire operations pipeline.
     * Used as part of the cache key so that different filter chains produce different caches.
     */
    protected function buildOperationsFingerprint(): string
    {
        if (empty($this->operations)) {
            return md5('');
        }

        $parts = array_map(fn (ImageOperationInterface $op) => $op->fingerprint(), $this->operations);

        return md5(implode('|', $parts));
    }

    /**
     * Resolve the target width and height from the source image and the registered size operation.
     * Falls back to original image dimensions if no explicit size operation was set.
     *
     * @return array{?int, ?int}  [width, height]
     */
    protected function resolveDimensionsFromSource(string $sourcePath): array
    {
        $originalDimensions = $this->processor->readImageDimensions($sourcePath);

        // Derive dimensions from the first size operation in the pipeline.
        foreach ($this->operations as $op) {
            if ($op instanceof ResizeOperation) {
                $opts = $op->options;
                if ($opts->width !== null && $opts->height !== null) {
                    return [$opts->width, $opts->height];
                }
                if ($opts->width !== null) {
                    $h = $originalDimensions
                        ? (int) round($opts->width * ($originalDimensions[1] / $originalDimensions[0]))
                        : null;

                    return [$opts->width, $h];
                }
                if ($opts->height !== null) {
                    $w = $originalDimensions
                        ? (int) round($opts->height * ($originalDimensions[0] / $originalDimensions[1]))
                        : null;

                    return [$w, $opts->height];
                }
            }

            if ($op instanceof StretchOperation || $op instanceof FitOperation) {
                // Both classes store width/height as private — access via fingerprint parsing
                // is fragile, so we use reflection or expose via interface.
                // Simpler: derive from the fingerprint string (e.g. "fit:400x200" → [400, 200]).
                $parts = explode(':', $op->fingerprint());
                if (isset($parts[1]) && str_contains($parts[1], 'x')) {
                    [$w, $h] = explode('x', $parts[1]);

                    return [(int) $w, (int) $h];
                }
            }

            if ($op instanceof CropOperation) {
                return [$op->options->width, $op->options->height];
            }
        }

        // No explicit size operation — use original dimensions.
        if ($originalDimensions !== null) {
            return [$originalDimensions[0], $originalDimensions[1]];
        }

        return [null, null];
    }

    /**
     * Ensure no size operation has already been set; throw otherwise.
     */
    protected function assertNoSizeOp(string $method): void
    {
        if ($this->sizeOp !== null) {
            throw new MediaBuilderException(
                "Cannot call {$method}() after {$this->sizeOp}() has already been set. " .
                'Only one size/crop operation is allowed per builder chain.'
            );
        }
    }

    /**
     * Append a filter operation to the pipeline.
     */
    protected function addFilter(string $type, array $params = []): static
    {
        $this->assertTransformationsAllowed($type);
        $this->operations[] = new FilterOperation($type, $params);

        return $this;
    }
}
