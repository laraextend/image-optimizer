<?php

namespace Laraextend\MediaToolkit\Console\Commands;

use Illuminate\Console\Command;
use Laraextend\MediaToolkit\Cache\ManifestCache;

class CacheWarm extends Command
{
    protected $signature = 'media:cache-warm
                            {--type= : Media type to warm (reserved for future use)}';

    protected $description = 'Regenerate cached media variants whose source files have changed';

    public function handle(ManifestCache $cache): int
    {
        $type = $this->option('type') ?: null;

        $this->info('Checking cache for outdated media variants...');

        $results = $cache->warmCache($type);

        $this->info(
            "✓ {$results['regenerated']} regenerated, {$results['skipped']} up to date."
        );

        foreach ($results['errors'] as $error) {
            $this->warn("⚠ {$error}");
        }

        return self::SUCCESS;
    }
}
