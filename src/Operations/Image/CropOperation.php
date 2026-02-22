<?php

namespace Laraextend\MediaToolkit\Operations\Image;

use Intervention\Image\Interfaces\ImageInterface;
use Laraextend\MediaToolkit\DTOs\CropOptions;

final class CropOperation implements ImageOperationInterface
{
    public function __construct(
        public readonly CropOptions $options,
    ) {}

    /**
     * Extract a region from the image without scaling.
     * String anchor values ('left', 'center', 'right', 'top', 'bottom') are resolved
     * to pixel offsets based on the current image dimensions at the time of execution.
     */
    public function apply(ImageInterface $image): ImageInterface
    {
        $x = $this->resolveOffset($this->options->x, $image->width(), $this->options->width);
        $y = $this->resolveOffset($this->options->y, $image->height(), $this->options->height);

        return $image->crop($this->options->width, $this->options->height, $x, $y);
    }

    public function fingerprint(): string
    {
        $x = is_int($this->options->x) ? $this->options->x : $this->options->x;
        $y = is_int($this->options->y) ? $this->options->y : $this->options->y;

        return "crop:{$this->options->width}x{$this->options->height}@{$x},{$y}";
    }

    /**
     * Resolve a string anchor or integer offset to a pixel integer.
     *
     * @param int|string $offset    Pixel int or 'left'|'center'|'right'|'top'|'bottom'
     * @param int        $imageDim  The current image dimension (width or height)
     * @param int        $cropDim   The size of the crop region in that dimension
     */
    private function resolveOffset(int|string $offset, int $imageDim, int $cropDim): int
    {
        if (is_int($offset)) {
            return max(0, $offset);
        }

        return match ($offset) {
            'left', 'top'   => 0,
            'center'        => max(0, (int) floor(($imageDim - $cropDim) / 2)),
            'right', 'bottom' => max(0, $imageDim - $cropDim),
            default         => 0,
        };
    }
}
