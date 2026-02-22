<?php

namespace Laraextend\MediaToolkit\Facades;

use Illuminate\Support\Facades\Facade;
use Laraextend\MediaToolkit\Builders\ImageBuilder;

/**
 * @method static ImageBuilder image(string $path)
 *
 * @see \Laraextend\MediaToolkit\MediaToolkitServiceProvider
 */
class Media extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'media-toolkit';
    }
}
