<?php

namespace Laraextend\MediaToolkit\DTOs;

use Laraextend\MediaToolkit\Enums\WatermarkPosition;

final class WatermarkOptions
{
    public function __construct(
        public readonly string            $source,
        public readonly WatermarkPosition $position = WatermarkPosition::BottomRight,
        public readonly int               $padding  = 10,
        public readonly int               $opacity  = 100,
    ) {}
}
