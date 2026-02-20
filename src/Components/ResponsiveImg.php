<?php

<<<<<<< Updated upstream
namespace Laraexten\ImageOptimizer\Components;

use Illuminate\View\ComponentAttributeBag;
use Illuminate\View\Component;
use Laraexten\ImageOptimizer\Helpers\ImageOptimizer;
=======
namespace Laraextend\ImageOptimizer\Components;

use Illuminate\View\Component;
use Laraextend\ImageOptimizer\Helpers\ImageOptimizer;
>>>>>>> Stashed changes

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
<<<<<<< Updated upstream
            attributes: $this->resolveAttributes(),
        );
    }

    protected function resolveAttributes(): array
    {
        $bladeAttributes = $this->attributes instanceof ComponentAttributeBag
            ? $this->attributes->getAttributes()
            : [];

        return array_replace($bladeAttributes, $this->extraAttributes);
    }
}
=======
            attributes: $this->extraAttributes,
        );
    }
}
>>>>>>> Stashed changes
