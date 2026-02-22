<?php

namespace Laraextend\MediaToolkit\Contracts;

interface MediaBuilderInterface
{
    /**
     * Serve the original file without any processing or optimization.
     * Disables all transformations, format changes and quality settings.
     */
    public function original(): static;

    /**
     * Skip the manifest cache — always re-process and overwrite cached files.
     */
    public function noCache(): static;

    /**
     * Returns the URL of the processed (or original) file.
     * Triggers the processing pipeline if not already cached.
     */
    public function url(): string;
}
