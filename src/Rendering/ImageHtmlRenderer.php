<?php

namespace Laraextend\MediaToolkit\Rendering;

class ImageHtmlRenderer
{
    protected const MIME_TYPES = [
        'webp'  => 'image/webp',
        'avif'  => 'image/avif',
        'jpg'   => 'image/jpeg',
        'jpeg'  => 'image/jpeg',
        'png'   => 'image/png',
    ];

    // ─────────────────────────────────────────────────────────────
    //  HTML TAG BUILDERS
    // ─────────────────────────────────────────────────────────────

    /**
     * Build a simple <img> without srcset.
     */
    public function buildSimpleImgTag(
        string  $url,
        string  $alt,
        ?int    $width,
        ?int    $height,
        string  $class,
        string  $loading,
        string  $fetchpriority,
        ?string $id,
        array   $attributes,
    ): string {
        $attrs = [
            'src'           => $url,
            'alt'           => $alt,
            'loading'       => $loading,
            'decoding'      => 'async',
            'fetchpriority' => $fetchpriority,
        ];

        if ($width)  { $attrs['width']  = $width; }
        if ($height) { $attrs['height'] = $height; }
        if ($class)  { $attrs['class']  = $class; }
        if ($id)     { $attrs['id']     = $id; }

        if ($fetchpriority === 'high' && $loading === 'lazy') {
            $attrs['loading'] = 'eager';
        }

        $attrs = array_merge($attrs, $attributes);

        return $this->renderTag('img', $attrs);
    }

    /**
     * Build an <img> with srcset and sizes.
     */
    public function buildResponsiveImgTag(
        array   $variants,
        string  $alt,
        ?int    $width,
        ?int    $height,
        string  $class,
        string  $loading,
        string  $fetchpriority,
        string  $sizes,
        ?string $id,
        array   $attributes,
    ): string {
        $targetWidth    = $width ?? $variants[0]['width'];
        $defaultVariant = $this->findClosestVariant($variants, $targetWidth);

        $srcset = $this->buildSrcset($variants);

        if ($height === null && $width !== null && $defaultVariant) {
            $height = (int) round($width * ($defaultVariant['height'] / $defaultVariant['width']));
        }

        $attrs = [
            'src'           => $defaultVariant['url'],
            'srcset'        => $srcset,
            'sizes'         => $sizes,
            'alt'           => $alt,
            'loading'       => $loading,
            'decoding'      => 'async',
            'fetchpriority' => $fetchpriority,
        ];

        if ($width)  { $attrs['width']  = $width; }
        if ($height) { $attrs['height'] = $height; }
        if ($class)  { $attrs['class']  = $class; }
        if ($id)     { $attrs['id']     = $id; }

        if ($fetchpriority === 'high' && $loading === 'lazy') {
            $attrs['loading'] = 'eager';
        }

        $attrs = array_merge($attrs, $attributes);

        return $this->renderTag('img', $attrs);
    }

    /**
     * Build a <picture> with <source> elements per modern format and a fallback <img>.
     *
     * @param  array  $formatVariants    ['webp' => [...variants], 'avif' => [...variants]]
     * @param  array  $fallbackVariants  Variants for the <img> fallback format
     * @param  array  $pictureAttributes Attributes forwarded to the <picture> element (e.g. wire:key)
     */
    public function buildPictureTag(
        array   $formatVariants,
        array   $fallbackVariants,
        string  $fallbackFormat,
        string  $alt,
        ?int    $width,
        ?int    $height,
        string  $class,
        string  $imgClass,
        string  $sourceClass,
        string  $loading,
        string  $fetchpriority,
        string  $sizes,
        ?string $id,
        array   $attributes,
        array   $pictureAttributes = [],
    ): string {
        if ($height === null && $width !== null && ! empty($fallbackVariants)) {
            $v      = $this->findClosestVariant($fallbackVariants, $width);
            $height = $v ? (int) round($width * ($v['height'] / $v['width'])) : null;
        }

        // <picture> opening tag
        $pictureAttrs = [];
        if ($class) { $pictureAttrs['class'] = $class; }
        if ($id)    { $pictureAttrs['id']    = $id; }
        $pictureAttrs = array_merge($pictureAttrs, $pictureAttributes);

        $html = $this->renderTag('picture', $pictureAttrs) . "\n";

        // <source> per modern format
        foreach ($formatVariants as $fmt => $variants) {
            $srcset = $this->buildSrcset($variants);
            $type   = self::MIME_TYPES[$fmt] ?? "image/{$fmt}";

            $sourceAttrs = [
                'type'   => $type,
                'srcset' => $srcset,
                'sizes'  => $sizes,
            ];
            if ($sourceClass) { $sourceAttrs['class'] = $sourceClass; }

            $html .= '    ' . $this->renderTag('source', $sourceAttrs) . "\n";
        }

        // Fallback <img>
        $fallbackDefault = ! empty($fallbackVariants)
            ? $this->findClosestVariant($fallbackVariants, $width ?? $fallbackVariants[0]['width'])
            : null;

        $imgAttrs = [
            'src'           => $fallbackDefault['url'] ?? '',
            'alt'           => $alt,
            'loading'       => $loading,
            'decoding'      => 'async',
            'fetchpriority' => $fetchpriority,
        ];

        if (! empty($fallbackVariants)) {
            $imgAttrs['srcset'] = $this->buildSrcset($fallbackVariants);
            $imgAttrs['sizes']  = $sizes;
        }

        if ($width)    { $imgAttrs['width']  = $width; }
        if ($height)   { $imgAttrs['height'] = $height; }
        if ($imgClass) { $imgAttrs['class']  = $imgClass; }

        $imgAttrs = array_merge($imgAttrs, $attributes);

        $html .= '    ' . $this->renderTag('img', $imgAttrs) . "\n";
        $html .= '</picture>';

        return $html;
    }

    // ─────────────────────────────────────────────────────────────
    //  HELPERS
    // ─────────────────────────────────────────────────────────────

    /**
     * Build a srcset string: "url 400w, url 800w, ..."
     */
    public function buildSrcset(array $variants): string
    {
        $parts = [];
        foreach ($variants as $v) {
            $parts[] = $v['url'] . ' ' . $v['width'] . 'w';
        }

        return implode(', ', $parts);
    }

    /**
     * Render an HTML tag from an attribute array.
     * Runs asset() on src and srcset values so they respect APP_URL.
     */
    public function renderTag(string $tag, array $attrs): string
    {
        if (isset($attrs['src'])) {
            $attrs['src'] = asset($attrs['src']);
        }

        if (isset($attrs['srcset'])) {
            $srcsetParts = explode(', ', $attrs['srcset']);
            foreach ($srcsetParts as &$part) {
                [$url, $descriptor] = explode(' ', $part, 2);
                $part = asset($url) . ' ' . $descriptor;
            }
            unset($part);
            $attrs['srcset'] = implode(', ', $srcsetParts);
        }

        $html = "<{$tag}";
        foreach ($attrs as $key => $value) {
            if ($value === null) {
                continue;
            }
            if ($value === true || $value === '') {
                // Boolean-like attribute (e.g. wire:navigate, controls)
                $html .= ' ' . e($key) . '=""';
                continue;
            }
            $html .= ' ' . e($key) . '="' . e($value) . '"';
        }
        $html .= '>';

        return $html;
    }

    /**
     * Find the variant whose width is closest to $targetWidth.
     */
    public function findClosestVariant(array $variants, int $targetWidth): ?array
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
     * Add fallback metadata attributes to the attributes array without overwriting
     * any that were explicitly set by the caller.
     */
    public function withFallbackMetadata(array $attributes, string $reason): array
    {
        if (! array_key_exists('data-media-toolkit-status', $attributes)) {
            $attributes['data-media-toolkit-status'] = 'original-fallback';
        }

        if (! array_key_exists('data-media-toolkit-reason', $attributes)) {
            $attributes['data-media-toolkit-reason'] = $reason;
        }

        return $attributes;
    }

    /**
     * Return an error string appropriate for the current environment.
     * Local: HTML comment. Production: empty string.
     */
    public function error(string $src): string
    {
        if (app()->isLocal()) {
            return "<!-- MEDIA ERROR: File not found: {$src} -->";
        }

        return '';
    }
}
