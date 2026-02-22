<?php

namespace Laraextend\MediaToolkit\Builders;

use Laraextend\MediaToolkit\Exceptions\MediaBuilderException;

/**
 * Phase-2 stub — Video support is not yet implemented.
 *
 * All methods throw MediaBuilderException until the implementation is added.
 * The class hierarchy and method signatures are intentionally defined here
 * so that IDEs and static-analysis tools can reason about the API surface
 * before the concrete implementation lands.
 *
 * Usage (future):
 *   Media::video('resources/videos/intro.mp4')
 *       ->resize(width: 1280)
 *       ->format('webm')
 *       ->url();
 */
class VideoBuilder extends BaseBuilder
{
    protected readonly string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function url(): string
    {
        throw new MediaBuilderException('VideoBuilder is not yet implemented (Phase 2).');
    }

    public function html(
        string  $alt        = '',
        string  $class      = '',
        ?string $id         = null,
        array   $attributes = [],
    ): string {
        throw new MediaBuilderException('VideoBuilder is not yet implemented (Phase 2).');
    }
}
