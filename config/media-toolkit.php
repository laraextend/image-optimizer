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

    ],

    // ─────────────────────────────────────────────────────────────
    //  FUTURE MEDIA TYPES  (Phase 2 / 3)
    // ─────────────────────────────────────────────────────────────

    'video' => [],

    'audio' => [],

];
