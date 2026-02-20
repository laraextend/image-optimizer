<?php

use Illuminate\Support\Facades\Blade;

test('img blade component renders optimized markup', function (): void {
    $html = Blade::render(
        '<x-laraextend::img :src="$src" alt="Component image" :width="320" format="jpg" />',
        ['src' => $this->landscapeImage],
    );

    expect($html)
        ->toContain('<img')
        ->toContain('alt="Component image"')
        ->toContain('width="320"')
        ->toContain('height="160"')
        ->toContain('.jpg');
});

test('img blade component does not forward unknown attributes automatically', function (): void {
    $html = Blade::render(
        '<x-laraextend::img :src="$src" alt="Component image" :width="320" format="jpg" wire:key="hero-image" data-track="1" />',
        ['src' => $this->landscapeImage],
    );

    expect($html)
        ->not->toContain('wire:key=')
        ->not->toContain('data-track=');
});

test('img blade component forwards extra attributes including wire directives', function (): void {
    $html = Blade::render(
        '<x-laraextend::img :src="$src" alt="Component image" :width="320" format="jpg" :extra-attributes="[\'wire:key\' => \'hero-image\', \'data-track\' => \'1\']" />',
        ['src' => $this->landscapeImage],
    );

    expect($html)
        ->toContain('wire:key="hero-image"')
        ->toContain('data-track="1"');
});
