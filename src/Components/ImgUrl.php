<?php

namespace Laraextend\ImageOptimizer\Components;

use Closure;
use Illuminate\View\Component;
use Laraextend\ImageOptimizer\Helpers\ImageOptimizer;

class ImgUrl extends Component
{
    public function __construct(
        public string $src,
        public ?int $width = null,
        public string $format = 'webp',
        public bool $original = false,
    ) {}

    public function render(): Closure
    {
        $url = app(ImageOptimizer::class)->url(
            src: $this->src,
            width: $this->width,
            format: $this->format,
            original: $this->original,
        );

        return fn () => $url;
    }
}
