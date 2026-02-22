<?php

namespace Laraextend\MediaToolkit\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Laraextend\MediaToolkit\Cache\ManifestCache;
use Laraextend\MediaToolkit\Failures\FailureRegistry;

class ProcessPending extends Command
{
    protected $signature = 'media:process-pending
                            {--list     : List all pending failures without processing}
                            {--clear    : Clear the entire failure registry}
                            {--memory=  : PHP memory_limit for processing (default: -1 = unlimited)}';

    protected $description = 'Retry processing of images that could not be generated at request time';

    public function handle(FailureRegistry $registry, ManifestCache $cache): int
    {
        // ── --list ───────────────────────────────────────────────────────────
        if ($this->option('list')) {
            $failures = $registry->all();

            if (empty($failures)) {
                $this->info('No pending failures recorded.');

                return self::SUCCESS;
            }

            $rows = [];
            foreach ($failures as $path => $entry) {
                $rows[] = [
                    $path,
                    $entry['reason'],
                    $entry['count'],
                    $entry['last_occurred'],
                ];
            }

            $this->table(['Path', 'Reason', 'Count', 'Last Occurred'], $rows);

            return self::SUCCESS;
        }

        // ── --clear ──────────────────────────────────────────────────────────
        if ($this->option('clear')) {
            $count = $registry->count();
            $registry->clear();
            $this->info("✓ Cleared {$count} " . ($count === 1 ? 'entry' : 'entries') . ' from the failure registry.');

            return self::SUCCESS;
        }

        // ── Process pending ──────────────────────────────────────────────────
        $failures = $registry->all();

        if (empty($failures)) {
            $this->info('No pending failures to process.');

            return self::SUCCESS;
        }

        // Raise memory limit so large images can be processed.
        $memoryLimit = $this->option('memory') ?? '-1';
        ini_set('memory_limit', $memoryLimit);

        $this->info('Processing ' . count($failures) . ' pending ' . (count($failures) === 1 ? 'entry' : 'entries') . '...');
        $this->newLine();

        $succeeded = 0;
        $skipped   = 0;
        $failed    = 0;

        foreach ($failures as $path => $entry) {
            $reason     = $entry['reason'];
            $params     = $entry['params'] ?? [];
            $sourcePath = base_path($path);

            // ── Source file still missing ────────────────────────────────────
            if (! File::exists($sourcePath)) {
                $this->warn("  ⚠  still not found: {$path}");
                $skipped++;

                continue;
            }

            // ── not_found entries whose file now exists → attempt processing ─
            // ── memory_limit / error entries → re-attempt with stored params ─

            if (empty($params)) {
                // not_found with no params: file now exists but we have no
                // sizing/format info — skip (the next browser request will succeed).
                $this->warn("  ⚠  no retry params stored (will be generated on next request): {$path}");
                $registry->remove($path);
                $skipped++;

                continue;
            }

            $displayWidth    = $params['display_width'] ?? null;
            $format          = $params['format']        ?? 'webp';
            $quality         = (int) ($params['quality'] ?? 80);
            $fingerprint     = $params['operations_fingerprint'] ?? md5('');
            $singleOnly      = (bool) ($params['single_only'] ?? true);

            if ($fingerprint !== md5('') && $fingerprint !== '') {
                $this->line("  ℹ  Note: operations/filters cannot be replicated offline — base variant only: {$path}");
            }

            try {
                $sourceModified = File::lastModified($sourcePath);

                $cache->getOrCreate(
                    sourcePath:             $sourcePath,
                    sourceModified:         $sourceModified,
                    displayWidth:           $displayWidth,
                    format:                 $format,
                    singleOnly:             $singleOnly,
                    operations:             [],
                    operationsFingerprint:  md5(''),
                    quality:                $quality,
                    noCache:                true,
                );

                $registry->remove($path);
                $this->info("  ✓  processed: {$path}");
                $succeeded++;
            } catch (\Throwable $e) {
                $this->error("  ✗  failed: {$path} — {$e->getMessage()}");
                $failed++;
            }
        }

        $this->newLine();
        $this->line("Done: {$succeeded} succeeded, {$skipped} skipped, {$failed} failed.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
