<?php

return [
    'driver' => env('MEDIA_TOOLKIT_DRIVER', 'auto'),
    'output_dir' => env('MEDIA_TOOLKIT_OUTPUT_DIR', 'img/optimized'),

    'responsive' => [
        'size_factors' => [0.5, 0.75, 1.0, 1.5, 2.0],
        'min_width' => 100,
    ],

    'quality' => [
        'webp' => 80,
        'avif' => 65,
        'jpg' => 82,
        'jpeg' => 82,
        'png' => 85,
    ],

    'defaults' => [
        'format' => 'webp',
        'picture_formats' => ['avif', 'webp'],
        'fallback_format' => 'jpg',
        'loading' => 'lazy',
        'fetchpriority' => 'auto',
        'sizes' => '100vw',
    ],
];
