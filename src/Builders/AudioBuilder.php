<?php

namespace Laraextend\MediaToolkit\Builders;

use Laraextend\MediaToolkit\Exceptions\MediaBuilderException;

/**
 * Phase-3 stub — Audio support is not yet implemented.
 *
 * All methods throw MediaBuilderException until the implementation is added.
 *
 * Usage (future):
 *   Media::audio('resources/audio/podcast.mp3')
 *       ->format('ogg')
 *       ->url();
 */
class AudioBuilder extends BaseBuilder
{
    protected readonly string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function url(): string
    {
        throw new MediaBuilderException('AudioBuilder is not yet implemented (Phase 3).');
    }

    public function html(
        string  $alt        = '',
        string  $class      = '',
        ?string $id         = null,
        array   $attributes = [],
    ): string {
        throw new MediaBuilderException('AudioBuilder is not yet implemented (Phase 3).');
    }
}
