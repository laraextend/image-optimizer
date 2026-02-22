<?php

namespace Laraextend\MediaToolkit\DTOs;

final class ResizeOptions
{
    public function __construct(
        public readonly ?int $width,
        public readonly ?int $height,
        public readonly bool $allowUpscale = false,
    ) {}
}
