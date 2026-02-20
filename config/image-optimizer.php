<?php

return [
<<<<<<< HEAD
    'driver' => env('IMAGE_OPTIMIZER_DRIVER', 'auto'),
    'output_dir' => env('IMAGE_OPTIMIZER_OUTPUT_DIR', 'img/optimized'),

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
=======

    /*
    |--------------------------------------------------------------------------
    | Output Directory (relativ zu public/)
    |--------------------------------------------------------------------------
    |
    | Verzeichnis, in dem optimierte Bildvarianten gespeichert werden.
    | Relativ zum public/-Ordner des Laravel-Projekts.
    |
    */
    'output_dir' => 'img/optimized',

    /*
    |--------------------------------------------------------------------------
    | Bildqualität pro Format (1–100)
    |--------------------------------------------------------------------------
    |
    | Niedrigere Werte = kleinere Dateigrößen, schlechtere Qualität.
    | Höhere Werte = bessere Qualität, größere Dateien.
    | PNG ignoriert den Qualitätswert (verlustfrei).
    |
    */
    'quality' => [
        'webp' => 80,
        'avif' => 65,
        'jpg'  => 82,
        'jpeg' => 82,
        'png'  => 85,
    ],

    /*
    |--------------------------------------------------------------------------
    | Responsive Breakpoint-Faktoren
    |--------------------------------------------------------------------------
    |
    | Multiplikatoren relativ zur angegebenen Anzeigebreite.
    | Aus diesen Faktoren werden die srcset-Varianten berechnet.
    | Varianten, die die Originalbreite überschreiten oder kleiner als
    | min_width sind, werden automatisch übersprungen.
    |
    */
    'size_factors' => [0.5, 0.75, 1.0, 1.5, 2.0],

    /*
    |--------------------------------------------------------------------------
    | Minimale Variantenbreite (in Pixel)
    |--------------------------------------------------------------------------
    |
    | Varianten, die schmäler als dieser Wert wären, werden nicht erzeugt.
    |
    */
    'min_width' => 100,

    /*
    |--------------------------------------------------------------------------
    | Standard-Formate
    |--------------------------------------------------------------------------
    |
    | default_format:  Standard-Ausgabeformat für img() und responsive_img().
    | picture_formats: Formate für <source>-Elemente in picture().
    | fallback_format: Fallback-<img> innerhalb von picture() für ältere Browser.
    |
    */
    'default_format'  => 'webp',
    'picture_formats' => ['avif', 'webp'],
    'fallback_format' => 'jpg',

    /*
    |--------------------------------------------------------------------------
    | Standard-HTML-Attribute
    |--------------------------------------------------------------------------
    |
    | loading:       'lazy' | 'eager' – Standard-Ladeverhalten für Bilder.
    | fetchpriority: 'auto' | 'high' | 'low' – Browser-Ladepriorität.
    | sizes:         Standard-sizes-Attribut für srcset-Bilder.
    |
    */
    'loading'       => 'lazy',
    'fetchpriority' => 'auto',
    'sizes'         => '100vw',

>>>>>>> claude/heuristic-wilson
];
