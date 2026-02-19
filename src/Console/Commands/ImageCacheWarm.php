<?php

namespace App\Console\Commands;

use App\Helpers\ImageOptimizer;
use Illuminate\Console\Command;

class ImageCacheWarm extends Command
{
    protected $signature = 'img:warm';
    protected $description = 'Veraltete Bild-Varianten neu generieren';

    public function handle(ImageOptimizer $optimizer): int
    {
        $this->info('Prüfe Cache auf veraltete Bilder...');

        $results = $optimizer->warmCache();

        $this->info("✓ {$results['regenerated']} neu generiert, {$results['skipped']} aktuell.");

        foreach ($results['errors'] as $error) {
            $this->warn("⚠ {$error}");
        }

        return self::SUCCESS;
    }
}
