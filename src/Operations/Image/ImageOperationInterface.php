<?php

namespace Laraextend\MediaToolkit\Operations\Image;

use Intervention\Image\Interfaces\ImageInterface;

interface ImageOperationInterface
{
    /**
     * Apply this operation to the given image and return the modified image.
     */
    public function apply(ImageInterface $image): ImageInterface;

    /**
     * Returns a stable string that uniquely identifies this operation and its parameters.
     * Used to build the cache key fingerprint.
     */
    public function fingerprint(): string;
}
