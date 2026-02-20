<?php

namespace Laraexten\ImageOptimizer\Components;

use Illuminate\View\Component;
use Laraexten\ImageOptimizer\Helpers\ImageOptimizer;

class Picture extends Component
{
    public function __construct(
        public string $src,
        public string $alt = '',
        public ?int $width = null,
        public ?int $height = null,
        public string $class = '',
        public string $imgClass = '',
        public string $sourceClass = '',
        public array $formats = ['avif', 'webp'],
        public string $fallbackFormat = 'jpg',
        public ?string $loading = null,
        public string $fetchpriority = 'auto',
        public string $sizes = '100vw',
        public ?string $id = null,
        public bool $original = false,
        public array $extraAttributes = [],
    ) {}

    public function render(): string
    {
        $resolvedLoading = match (true) {
            $this->fetchpriority === 'high' && $this->loading !== 'eager' => null,
            default => $this->loading,
        };

        return app(ImageOptimizer::class)->renderPicture(
            src: $this->src,
            alt: $this->alt,
            width: $this->width,
            height: $this->height,
            class: $this->class,
            imgClass: $this->imgClass,
            sourceClass: $this->sourceClass,
            formats: $this->formats,
            fallbackFormat: $this->fallbackFormat,
            loading: $resolvedLoading,
            fetchpriority: $this->fetchpriority,
            sizes: $this->sizes,
            id: $this->id,
            original: $this->original,
            attributes: $this->extraAttributes,
        );
    }
}
