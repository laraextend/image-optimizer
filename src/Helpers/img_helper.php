<?php

use App\Helpers\ImageOptimizer;

// ─────────────────────────────────────────────────────────────
//  img() — Einzelnes optimiertes Bild, OHNE srcset
// ─────────────────────────────────────────────────────────────

if (! function_exists('img')) {
    /**
     * Optimiertes Einzelbild (resize + komprimiert, kein srcset).
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
     *       original: true,   // ← Originaldatei, kein Processing
     *   ) !!}
     */
    function img(
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
//  responsive_img() — <img> mit srcset (responsive)
// ─────────────────────────────────────────────────────────────

if (! function_exists('responsive_img')) {
    /**
     * Responsive <img> mit srcset + sizes.
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
        string $format = 'webp',
        string $loading = 'lazy',
        string $fetchpriority = 'auto',
        string $sizes = '100vw',
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
//  picture() — <picture> mit mehreren Formaten
// ─────────────────────────────────────────────────────────────

if (! function_exists('picture')) {
    /**
     * <picture> mit <source> pro Format + Fallback <img>.
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
     * Erzeugt:
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
        string $class = '',              // class für <picture>
        string $imgClass = '',           // class für <img>
        string $sourceClass = '',        // class für <source>
        array $formats = ['avif', 'webp'],
        string $fallbackFormat = 'jpg',
        ?string $loading = null,          // null = automatisch aus fetchpriority
        string $fetchpriority = 'auto',
        string $sizes = '100vw',
        ?string $id = null,
        bool $original = false,
        array $attributes = [],
    ): string {
        // Wenn loading explizit 'lazy' aber fetchpriority 'high' → ignoriere loading
        // Nur ein explizites loading='eager' oder loading='lazy' bei fetchpriority != 'high' wird respektiert
        $resolvedLoading = match (true) {
            $fetchpriority === 'high' && $loading !== 'eager' => null, // → renderPicture setzt 'eager'
            default => $loading,
        };

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
            loading: $resolvedLoading,
            fetchpriority: $fetchpriority,
            sizes: $sizes,
            id: $id,
            original: $original,
            attributes: $attributes,
        );
    }
}

// ─────────────────────────────────────────────────────────────
//  img_url() — Nur die URL zurückgeben
// ─────────────────────────────────────────────────────────────

if (! function_exists('img_url')) {
    /**
     * Gibt nur die URL des optimierten Bildes zurück.
     *
     *   <div style="background-image: url('{{ img_url(src: '...', width: 800) }}')">
     *   <meta property="og:image" content="{{ img_url(src: '...', width: 1200, format: 'jpg') }}">
     *   {{ img_url(src: '...', original: true) }}  ← Originaldatei-URL
     */
    function img_url(
        string $src,
        ?int $width = null,
        string $format = 'webp',
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
