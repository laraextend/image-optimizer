<?php

namespace Laraexten\ImageOptimizer\Console\Commands;

use Laraexten\ImageOptimizer\Helpers\ImageOptimizer;
use Illuminate\Console\Command;

class ImageCacheWarm extends Command
{
    protected $signature = 'img:warm';
    protected $description = 'Regenerate outdated image variants';

    public function handle(ImageOptimizer $optimizer): int
    {
        $this->info('Checking cache for outdated images...');

        $results = $optimizer->warmCache();

        $this->info("✓ {$results['regenerated']} newly generated, {$results['skipped']} current.");

        foreach ($results['errors'] as $error) {
            $this->warn("⚠ {$error}");
        }

        return self::SUCCESS;
    }
}
