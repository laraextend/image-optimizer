<?php

namespace Laraextend\MediaToolkit\Failures;

use Illuminate\Support\Facades\File;

/**
 * Persists a JSON record of images that could not be processed at request time.
 *
 * Storage: storage/media-toolkit/failures.json
 *
 * JSON schema (keyed by source path):
 * {
 *   "resources/images/big.jpg": {
 *     "reason":         "memory_limit",   // "not_found" | "error" | "memory_limit"
 *     "count":          3,
 *     "first_occurred": "2026-02-22T12:00:00+00:00",
 *     "last_occurred":  "2026-02-22T12:05:00+00:00",
 *     "params": {
 *       "display_width":           450,
 *       "format":                  "webp",
 *       "quality":                 80,
 *       "operations_fingerprint":  "abc123",
 *       "single_only":             true
 *     }
 *   }
 * }
 *
 * Only "memory_limit" and "error" entries include params (not_found entries
 * cannot be retried since the source file does not exist).
 */
class FailureRegistry
{
    public function __construct(private readonly string $storagePath) {}

    // ─────────────────────────────────────────────────────────────
    //  PUBLIC API
    // ─────────────────────────────────────────────────────────────

    /**
     * Record (or increment) a failure for the given source path.
     *
     * @param  string  $path    Source path (relative to base_path)
     * @param  string  $reason  'not_found' | 'error' | 'memory_limit'
     * @param  array   $params  Retry parameters — only for 'error' and 'memory_limit'
     */
    public function record(string $path, string $reason, array $params = []): void
    {
        $data = $this->load();
        $now  = (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM);

        if (isset($data[$path])) {
            $data[$path]['count']        += 1;
            $data[$path]['last_occurred'] = $now;
            // Keep the most recent reason and params
            $data[$path]['reason'] = $reason;
            if (! empty($params)) {
                $data[$path]['params'] = $params;
            }
        } else {
            $data[$path] = [
                'reason'         => $reason,
                'count'          => 1,
                'first_occurred' => $now,
                'last_occurred'  => $now,
                'params'         => $params,
            ];
        }

        $this->save($data);
    }

    /**
     * Return all recorded failures.
     *
     * @return array<string, array>
     */
    public function all(): array
    {
        return $this->load();
    }

    /**
     * Remove a single entry from the registry.
     */
    public function remove(string $path): void
    {
        $data = $this->load();
        unset($data[$path]);
        $this->save($data);
    }

    /**
     * Remove all entries from the registry.
     */
    public function clear(): void
    {
        $this->save([]);
    }

    /**
     * Return the number of recorded failures.
     */
    public function count(): int
    {
        return count($this->load());
    }

    // ─────────────────────────────────────────────────────────────
    //  INTERNAL HELPERS
    // ─────────────────────────────────────────────────────────────

    private function load(): array
    {
        if (! File::exists($this->storagePath)) {
            return [];
        }

        $json = File::get($this->storagePath);
        $data = json_decode($json, true);

        return is_array($data) ? $data : [];
    }

    private function save(array $data): void
    {
        $dir = dirname($this->storagePath);

        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, recursive: true);
        }

        File::put($this->storagePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
