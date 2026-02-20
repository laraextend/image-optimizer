<?php

use Laraexten\ImageOptimizer\Helpers\ImageOptimizer;

// ─────────────────────────────────────────────────────────────
//  img() - Single optimized image, WITHOUT srcset
// ─────────────────────────────────────────────────────────────

if (! function_exists('img')) {
    /**
     * Optimized single image (resize + compressed, no srcset).
     *
     *   {!! img(
     *       src: 'resources/views/pages/home/logo.jpg',
     *       alt: 'Logo',
     *       width: 200,
     *       format: 'webp',
     *   ) !!}
     *
     *   {!! img(
     *       src: 'resources/views/pages/home/logo.png',
     *       alt: 'Logo',
     *       original: true,   // ← Original file, no processing
     *   ) !!}
     */
    function img(
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
        return app(ImageOptimizer::class)->renderSingle(
            src: $src,
            alt: $alt,
            width: $width,
            height: $height,
            class: $class,
            format: $format,
            loading: $loading,
            fetchpriority: $fetchpriority,
            id: $id,
            original: $original,
            attributes: $attributes,
        );
    }
}

// ─────────────────────────────────────────────────────────────
//  responsive_img() - <img> with srcset (responsive)
// ─────────────────────────────────────────────────────────────

if (! function_exists('responsive_img')) {
    /**
     * Responsive <img> with srcset + sizes.
     *
     *   {!! responsive_img(
     *       src: 'resources/views/pages/home/hero.jpg',
     *       alt: 'Hero',
     *       width: 800,
     *       format: 'webp',
     *       fetchpriority: 'high',
     *       sizes: '(max-width: 768px) 100vw, 800px',
     *   ) !!}
     */
    function responsive_img(
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
        return app(ImageOptimizer::class)->renderResponsive(
            src: $src,
            alt: $alt,
            width: $width,
            height: $height,
            class: $class,
            format: $format,
            loading: $loading,
            fetchpriority: $fetchpriority,
            sizes: $sizes,
            id: $id,
            original: $original,
            attributes: $attributes,
        );
    }
}

// ─────────────────────────────────────────────────────────────
//  picture() - <picture> with multiple formats
// ─────────────────────────────────────────────────────────────

if (! function_exists('picture')) {
    /**
     * <picture> with <source> per format + fallback <img>.
     *
     *   {!! picture(
     *       src: 'resources/views/pages/home/hero.jpg',
     *       alt: 'Hero',
     *       width: 800,
     *       formats: ['avif', 'webp'],
     *       fallbackFormat: 'jpg',
     *       fetchpriority: 'high',
     *       sizes: '(max-width: 768px) 100vw, 800px',
     *   ) !!}
     *
     * Generates:
     *   <picture>
     *     <source type="image/avif" srcset="...avif 400w, ...avif 600w, ...avif 800w" sizes="...">
     *     <source type="image/webp" srcset="...webp 400w, ...webp 600w, ...webp 800w" sizes="...">
     *     <img src="...jpg" srcset="...jpg 400w, ...jpg 600w, ...jpg 800w" sizes="..." ... >
     *   </picture>
     */
    function picture(
        string $src,
        string $alt = '',
        ?int $width = null,
        ?int $height = null,
        string $class = '',              // class for <picture>
        string $imgClass = '',           // class for <img>
        string $sourceClass = '',        // class for <source>
        ?array $formats = null,
        ?string $fallbackFormat = null,
        ?string $loading = null,
        ?string $fetchpriority = null,
        ?string $sizes = null,
        ?string $id = null,
        bool $original = false,
        array $attributes = [],
    ): string {
        return app(ImageOptimizer::class)->renderPicture(
            src: $src,
            alt: $alt,
            width: $width,
            height: $height,
            class: $class,
            imgClass: $imgClass,
            sourceClass: $sourceClass,
            formats: $formats,
            fallbackFormat: $fallbackFormat,
            loading: $loading,
            fetchpriority: $fetchpriority,
            sizes: $sizes,
            id: $id,
            original: $original,
            attributes: $attributes,
        );
    }
}

// ─────────────────────────────────────────────────────────────
//  img_url() - Return only the URL
// ─────────────────────────────────────────────────────────────

if (! function_exists('img_url')) {
    /**
     * Returns only the URL of the optimized image.
     *
     *   <div style="background-image: url('{{ img_url(src: '...', width: 800) }}')">
     *   <meta property="og:image" content="{{ img_url(src: '...', width: 1200, format: 'jpg') }}">
     *   {{ img_url(src: '...', original: true) }}  ← Original file URL
     */
    function img_url(
        string $src,
        ?int $width = null,
        ?string $format = null,
        bool $original = false,
    ): string {
        return app(ImageOptimizer::class)->url(
            src: $src,
            width: $width,
            format: $format,
            original: $original,
        );
    }
}
