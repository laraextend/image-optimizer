<?php

namespace Laraextend\MediaToolkit\Operations\Image;

use Intervention\Image\Interfaces\ImageInterface;
use Laraextend\MediaToolkit\DTOs\WatermarkOptions;

final class WatermarkOperation implements ImageOperationInterface
{
    public function __construct(
        private readonly WatermarkOptions $options,
        private readonly string           $absoluteSourcePath,
    ) {}

    /**
     * Overlay the watermark image at the configured position.
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        return $image->place(
            $this->absoluteSourcePath,
            $this->options->position->value,
            $this->options->padding,
            $this->options->padding,
            $this->options->opacity,
        );
    }

    public function fingerprint(): string
    {
        $hash = substr(md5($this->absoluteSourcePath), 0, 8);

        return "watermark:{$hash}:{$this->options->position->value}:{$this->options->padding}:{$this->options->opacity}";
    }
}
