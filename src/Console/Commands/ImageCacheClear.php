<?php

namespace Laraextend\MediaToolkit\Console\Commands;

use Laraextend\MediaToolkit\Helpers\ImageOptimizer;
use Illuminate\Console\Command;

class ImageCacheClear extends Command
{
    protected $signature = 'media:img-clear';
    protected $description = 'Delete all optimized image variants';

    public function handle(ImageOptimizer $optimizer): int
    {
        $count = $optimizer->clearCache();
        $this->info("✓ {$count} cache entries deleted.");

        return self::SUCCESS;
    }
}