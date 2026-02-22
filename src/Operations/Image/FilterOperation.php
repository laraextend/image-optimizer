<?php

namespace Laraextend\MediaToolkit\Operations\Image;

use Intervention\Image\Drivers\Gd\Core as GdCore;
use Intervention\Image\Interfaces\ImageInterface;

final class FilterOperation implements ImageOperationInterface
{
    /**
     * @param string $type   One of: grayscale, sepia, negate, brightness, contrast,
     *                       colorize, blur, smooth, rotate, flipH, flipV, flipBoth
     * @param array  $params Filter-specific parameters
     */
    public function __construct(
        private readonly string $type,
        private readonly array  $params = [],
    ) {}

    public function apply(ImageInterface $image): ImageInterface
    {
        return match ($this->type) {
            'grayscale' => $image->greyscale(),
            'sepia'     => $image->greyscale()->colorize(38, 27, 12),
            'negate'    => $image->invert(),

            // brightness: public API -255..+255, Intervention v3 -100..+100
            'brightness' => $image->brightness(
                $this->mapRange((int) ($this->params[0] ?? 0), -255, 255, -100, 100)
            ),

            // contrast: public API -100..+100 matches Intervention v3 directly
            'contrast' => $image->contrast((int) ($this->params[0] ?? 0)),

            // colorize: public API -255..+255, Intervention v3 -100..+100
            'colorize' => $image->colorize(
                $this->mapRange((int) ($this->params[0] ?? 0), -255, 255, -100, 100),
                $this->mapRange((int) ($this->params[1] ?? 0), -255, 255, -100, 100),
                $this->mapRange((int) ($this->params[2] ?? 0), -255, 255, -100, 100),
            ),

            'blur'   => $this->applyBlur($image),
            'smooth' => $this->applySmooth($image),

            'rotate' => $this->params[0] === 'auto'
                ? $image->orientate()
                : $image->rotate((int) ($this->params[0] ?? 0)),

            'flipH'    => $image->flip(),
            'flipV'    => $image->flop(),
            'flipBoth' => $image->flip()->flop(),

            default => $image,
        };
    }

    public function fingerprint(): string
    {
        return 'filter:' . $this->type . ':' . implode(',', $this->params);
    }

    /**
     * Apply Gaussian blur by repeating the blur filter $amount times.
     */
    private function applyBlur(ImageInterface $image): ImageInterface
    {
        $amount = max(1, (int) ($this->params[0] ?? 1));

        for ($i = 0; $i < $amount; $i++) {
            $image = $image->blur(1);
        }

        return $image;
    }

    /**
     * Apply smooth/sharpen filter.
     * GD: uses imagefilter(IMG_FILTER_SMOOTH) via the native GD resource.
     * Imagick: approximated via blur for positive (smooth) or sharpen for negative.
     */
    private function applySmooth(ImageInterface $image): ImageInterface
    {
        $level = (int) ($this->params[0] ?? 0);

        // GD path: direct IMG_FILTER_SMOOTH access
        if ($image->core() instanceof GdCore) {
            $gdResource = $image->core()->native();
            imagefilter($gdResource, IMG_FILTER_SMOOTH, $level);

            return $image;
        }

        // Imagick fallback: positive level = blur, negative = sharpen
        if ($level > 0) {
            $image = $image->blur((int) ceil($level * 0.5));
        } elseif ($level < 0) {
            $image = $image->sharpen(min(100, (int) ceil(abs($level) * 10)));
        }

        return $image;
    }

    /**
     * Linearly map a value from one range to another.
     */
    private function mapRange(int $value, int $inMin, int $inMax, int $outMin, int $outMax): int
    {
        $value = max($inMin, min($inMax, $value));

        return (int) round($outMin + ($value - $inMin) * ($outMax - $outMin) / ($inMax - $inMin));
    }
}
