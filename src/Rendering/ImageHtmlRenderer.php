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
     * Build an SVG placeholder <img> as a data URI.
     *
     * Used when 'placeholder' mode is configured for on_not_found or on_error.
     * The image is fully self-contained (no external request) and renders a
     * gray rectangle with a centered text label.
     *
     * @param  int|null $width    Target width in pixels (defaults to 400)
     * @param  int|null $height   Target height in pixels (defaults to 300)
     * @param  string   $text     Label drawn in the center (e.g. "Image not available")
     * @param  string   $bgColor  Background hex color
     * @param  string   $alt      <img alt> value; falls back to $text when empty
     */
    public function buildPlaceholderImg(
        ?int   $width,
        ?int   $height,
        string $text,
        string $bgColor = '#94a3b8',
        string $alt     = '',
    ): string {
        $w        = $width  ?? 400;
        $h        = $height ?? 300;
        $fontSize = max(12, min(18, (int) round($w / 22)));

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $w . '" height="' . $h . '">'
             . '<rect width="100%" height="100%" fill="' . htmlspecialchars($bgColor, ENT_QUOTES) . '"/>'
             . '<text x="50%" y="50%" font-family="system-ui,sans-serif" font-size="' . $fontSize . '" '
             . 'fill="#ffffff" text-anchor="middle" dominant-baseline="middle">'
             . htmlspecialchars($text, ENT_QUOTES)
             . '</text>'
             . '</svg>';

        $dataUri = 'data:image/svg+xml;base64,' . base64_encode($svg);

        return '<img src="' . $dataUri . '" width="' . $w . '" height="' . $h
             . '" alt="' . htmlspecialchars($alt ?: $text, ENT_QUOTES) . '">';
    }

    /**
     * Build a broken-image <img> tag.
     *
     * The src intentionally points to a non-existent URL so the browser
     * renders its native broken-image icon — useful during development.
     *
     * @param  string $src  The original (non-existing) source path or URL
     * @param  string $alt  <img alt> value
     */
    public function buildBrokenImg(string $src, string $alt = ''): string
    {
        return '<img src="' . htmlspecialchars($src, ENT_QUOTES)
             . '" alt="' . htmlspecialchars($alt, ENT_QUOTES) . '">';
    }

    /**
     * Return an error string appropriate for the current environment.
     * Local: HTML comment. Production: empty string.
     *
     * @deprecated  Use buildPlaceholderImg() or buildBrokenImg() via ImageBuilder config instead.
     */
    public function error(string $src): string
    {
        if (app()->isLocal()) {
            return "<!-- MEDIA ERROR: File not found: {$src} -->";
        }

        return '';
    }
}
