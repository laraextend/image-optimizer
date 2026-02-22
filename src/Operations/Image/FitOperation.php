<?php

namespace Laraextend\MediaToolkit\Operations\Image;

use Intervention\Image\Interfaces\ImageInterface;

final class FitOperation implements ImageOperationInterface
{
    public function __construct(
        private readonly int $width,
        private readonly int $height,
    ) {}

    /**
     * Scale to fill the given frame completely, then crop any overflow from the center.
     * Equivalent to CSS background-size: cover.
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        return $image->cover($this->width, $this->height);
    }

    public function fingerprint(): string
    {
        return "fit:{$this->width}x{$this->height}";
    }
}
