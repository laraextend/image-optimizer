<?php

return [

    // ─────────────────────────────────────────────────────────────
    //  DRIVER
    //  'auto' detects the best available extension (Imagick › GD).
    //  Explicit values: 'imagick' | 'gd'
    // ─────────────────────────────────────────────────────────────

    'driver' => env('MEDIA_TOOLKIT_DRIVER', 'auto'),

    // ─────────────────────────────────────────────────────────────
    //  OUTPUT DIRECTORY
    //  Relative to public_path(). Processed files are stored here.
    // ─────────────────────────────────────────────────────────────

    'output_dir' => env('MEDIA_TOOLKIT_OUTPUT_DIR', 'media/optimized'),

    // ─────────────────────────────────────────────────────────────
    //  IMAGE
    // ─────────────────────────────────────────────────────────────

    'image' => [

        'quality' => [
            'webp'  => 80,
            'avif'  => 65,
            'jpg'   => 82,
            'jpeg'  => 82,
            'png'   => 85,
        ],

        'responsive' => [
            'size_factors' => [0.5, 0.75, 1.0, 1.5, 2.0],
            'min_width'    => 100,
        ],

        'defaults' => [
            'format'          => 'webp',
            'picture_formats' => ['avif', 'webp'],
            'fallback_format' => 'jpg',
            'loading'         => 'lazy',
            'fetchpriority'   => 'auto',
            'sizes'           => '100vw',
        ],

        // ─────────────────────────────────────────────────────────────
        //  ERROR HANDLING
        //
        //  on_not_found    — source file does not exist on disk
        //  on_error        — file exists but processing/encoding fails
        //  on_memory_limit — GD skips processing because it would exceed PHP memory_limit
        //
        //  Modes for on_not_found / on_error:
        //    'placeholder' → gray SVG <img> with a text label (default)
        //    'broken'      → <img> with the original (non-existing) src,
        //                    so the browser renders its native broken-image icon
        //    'exception'   → throw MediaBuilderException
        //
        //  Additional mode for on_memory_limit:
        //    'original'    → copy & serve the raw source file unchanged
        // ─────────────────────────────────────────────────────────────

        'errors' => [
            'on_not_found'    => env('MEDIA_ON_NOT_FOUND',    'placeholder'),
            'on_error'        => env('MEDIA_ON_ERROR',        'placeholder'),
            'on_memory_limit' => env('MEDIA_ON_MEMORY_LIMIT', 'placeholder'),

            'not_found_text'     => 'Media could not be found.',
            'error_text'         => 'Media could not be displayed!',
            'memory_limit_text'  => 'Media will be displayed shortly.',

            'not_found_color'    => '#f87171',   // red-400
            'error_color'        => '#f87171',   // red-400
            'memory_limit_color' => '#9ca3af',   // gray-400
        ],

        // ─────────────────────────────────────────────────────────────
        //  LOGGING
        //
        //  Errors (not_found, processing errors, memory bypasses) are
        //  written to the Laravel log so you can monitor them in
        //  production without needing to inspect the HTML output.
        //
        //  A machine-readable failure registry is also maintained at
        //  storage/media-toolkit/failures.json for offline retry via
        //  php artisan media:process-pending
        // ─────────────────────────────────────────────────────────────

        'logging' => [
            'enabled' => env('MEDIA_LOGGING_ENABLED', true),

            // null = Laravel's default log channel (LOG_CHANNEL in .env)
            // Set to 'single', 'daily', 'stack', etc. to use a dedicated channel
            'channel' => env('MEDIA_LOG_CHANNEL', null),

            'level' => [
                'not_found'    => 'warning',
                'error'        => 'error',
                'memory_limit' => 'notice',
            ],
        ],

    ],

    // ─────────────────────────────────────────────────────────────
    //  FUTURE MEDIA TYPES  (Phase 2 / 3)
    // ─────────────────────────────────────────────────────────────

    'video' => [],

    'audio' => [],

];
