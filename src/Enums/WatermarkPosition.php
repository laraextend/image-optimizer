<?php

namespace Laraextend\MediaToolkit\Enums;

enum WatermarkPosition: string
{
    case TopLeft      = 'top-left';
    case TopCenter    = 'top-center';
    case TopRight     = 'top-right';
    case CenterLeft   = 'center-left';
    case Center       = 'center';
    case CenterRight  = 'center-right';
    case BottomLeft   = 'bottom-left';
    case BottomCenter = 'bottom-center';
    case BottomRight  = 'bottom-right';
}
