<?php

namespace Laraextend\MediaToolkit\Builders;

use Laraextend\MediaToolkit\Exceptions\MediaBuilderException;

/**
 * Phase-4 stub — SVG support is not yet implemented.
 *
 * All methods throw MediaBuilderException until the implementation is added.
 *
 * Usage (future):
 *   Media::svg('resources/images/icon.svg')
 *       ->url();
 */
class SvgBuilder extends BaseBuilder
{
    protected readonly string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function url(): string
    {
        throw new MediaBuilderException('SvgBuilder is not yet implemented (Phase 4).');
    }

    public function html(
        string  $alt        = '',
        string  $class      = '',
        ?string $id         = null,
        array   $attributes = [],
    ): string {
        throw new MediaBuilderException('SvgBuilder is not yet implemented (Phase 4).');
    }
}
