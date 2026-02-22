<?php

namespace Laraextend\MediaToolkit\DTOs;

final class CropOptions
{
    public function __construct(
        public readonly int $width,
        public readonly int $height,
        public readonly int|string $x = 0,
        public readonly int|string $y = 0,
    ) {}
}
