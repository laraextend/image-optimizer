<?php

namespace Laraextend\MediaToolkit\Contracts;

interface HasHtmlOutputInterface
{
    /**
     * Override the loading attribute.
     * Allowed: 'lazy', 'eager'
     */
    public function loading(string $loading): static;

    /**
     * Override the fetchpriority attribute.
     * Allowed: 'auto', 'high', 'low'
     * Setting 'high' automatically forces loading='eager'.
     */
    public function fetchpriority(string $priority): static;

    /**
     * Returns the HTML output.
     * The rendered element depends on the active output mode:
     *   (default)         → <img src="..." ...>
     *   ->responsive(...) → <img src="..." srcset="..." sizes="...">
     *   ->picture(...)    → <picture><source .../><img .../></picture>
     *
     * @param string      $alt        Alt text for accessibility
     * @param string      $class      CSS class(es) — applied to <img> or <picture>
     * @param string|null $id         HTML id attribute
     * @param array       $attributes Additional HTML attributes as key-value pairs
     */
    public function html(
        string  $alt        = '',
        string  $class      = '',
        ?string $id         = null,
        array   $attributes = [],
    ): string;
}
