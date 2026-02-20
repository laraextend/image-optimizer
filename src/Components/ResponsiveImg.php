<?php

namespace Laraexten\ImageOptimizer\Components;

use Illuminate\View\Component;
use Laraexten\ImageOptimizer\Helpers\ImageOptimizer;

class ResponsiveImg extends Component
{
    public function __construct(
        public string $src,
        public string $alt = '',
        public ?int $width = null,
        public ?int $height = null,
        public string $class = '',
        public string $format = 'webp',
        public string $loading = 'lazy',
        public string $fetchpriority = 'auto',
        public string $sizes = '100vw',
        public ?string $id = null,
        public bool $original = false,
        public array $extraAttributes = [],
    ) {}

    public function render(): string
    {
        return app(ImageOptimizer::class)->renderResponsive(
            src: $this->src,
            alt: $this->alt,
            width: $this->width,
            height: $this->height,
            class: $this->class,
            format: $this->format,
            loading: $this->loading,
            fetchpriority: $this->fetchpriority,
            sizes: $this->sizes,
            id: $this->id,
            original: $this->original,
            attributes: $this->extraAttributes,
        );
    }
}
