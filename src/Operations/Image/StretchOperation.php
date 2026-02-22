<?php

namespace Laraextend\MediaToolkit\Operations\Image;

use Intervention\Image\Interfaces\ImageInterface;

final class StretchOperation implements ImageOperationInterface
{
    public function __construct(
        private readonly int $width,
        private readonly int $height,
    ) {}

    /**
     * Resize to exact dimensions — aspect ratio is ignored.
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        return $image->resize($this->width, $this->height);
    }

    public function fingerprint(): string
    {
        return "stretch:{$this->width}x{$this->height}";
    }
}
