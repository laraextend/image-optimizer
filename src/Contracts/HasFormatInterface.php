<?php

namespace Laraextend\MediaToolkit\Contracts;

interface HasFormatInterface
{
    /**
     * Override the output format.
     * Allowed: 'webp', 'avif', 'jpg', 'jpeg', 'png'
     */
    public function format(string $format): static;

    /**
     * Override the output quality (1–100).
     */
    public function quality(int $quality): static;
}
