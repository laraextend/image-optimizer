<?php

namespace Laraextend\MediaToolkit\Operations\Image;

use Intervention\Image\Interfaces\ImageInterface;
use Laraextend\MediaToolkit\DTOs\ResizeOptions;

final class ResizeOperation implements ImageOperationInterface
{
    public function __construct(
        public readonly ResizeOptions $options,
    ) {}

    public function apply(ImageInterface $image): ImageInterface
    {
        $width  = $this->options->width;
        $height = $this->options->height;

        // Cap at original dimensions unless upscaling is explicitly allowed.
        if (! $this->options->allowUpscale) {
            if ($width !== null) {
                $width = min($width, $image->width());
            }
            if ($height !== null) {
                $height = min($height, $image->height());
            }
        }

        if ($width !== null && $height !== null) {
            // Both dimensions: fit inside the box, keep aspect ratio, no crop, no stretch.
            return $image->contain($width, $height);
        }

        if ($width !== null) {
            return $image->scale(width: $width);
        }

        if ($height !== null) {
            return $image->scale(height: $height);
        }

        // Fallback: no-op (should not happen due to builder validation).
        return $image;
    }

    public function fingerprint(): string
    {
        $w       = $this->options->width ?? 'null';
        $h       = $this->options->height ?? 'null';
        $upscale = $this->options->allowUpscale ? '1' : '0';

        return "resize:{$w}x{$h}:upscale={$upscale}";
    }
}
