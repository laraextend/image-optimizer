<?php

namespace Laraextend\MediaToolkit\Console\Commands;

use Illuminate\Console\Command;
use Laraextend\MediaToolkit\Cache\ManifestCache;

class CacheClear extends Command
{
    protected $signature = 'media:cache-clear
                            {--type= : Media type to clear (reserved for future use)}';

    protected $description = 'Delete all cached media variant files';

    public function handle(ManifestCache $cache): int
    {
        $type  = $this->option('type') ?: null;
        $count = $cache->clearCache($type);

        $this->info("✓ {$count} cache " . ($count === 1 ? 'entry' : 'entries') . ' deleted.');

        return self::SUCCESS;
    }
}
