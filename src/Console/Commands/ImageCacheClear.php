<?php

namespace App\Console\Commands;

use App\Helpers\ImageOptimizer;
use Illuminate\Console\Command;

class ImageCacheClear extends Command
{
    protected $signature = 'img:clear';
    protected $description = 'Alle optimierten Bild-Varianten löschen';

    public function handle(ImageOptimizer $optimizer): int
    {
        $count = $optimizer->clearCache();
        $this->info("✓ {$count} Cache-Einträge gelöscht.");

        return self::SUCCESS;
    }
}
