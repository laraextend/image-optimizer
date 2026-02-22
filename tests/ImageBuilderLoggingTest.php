<?php

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;
use Laraextend\MediaToolkit\Failures\FailureRegistry;
use Laraextend\MediaToolkit\Facades\Media;
use Laraextend\MediaToolkit\Processing\ImageProcessor;

// ─────────────────────────────────────────────────────────────
//  Helpers (file-scoped)
// ─────────────────────────────────────────────────────────────

/**
 * Create a FailureRegistry backed by a temp file that is cleaned up
 * after each test.
 */
function makeTestRegistry(): FailureRegistry
{
    $path = storage_path('media-toolkit/test-failures.json');
    File::delete($path);

    return new FailureRegistry($path);
}

/**
 * Register a Log listener that collects log entries into a referenced array.
 * Returns the collected entries by reference.
 */
function collectLogs(): array
{
    $entries = [];
    Log::listen(function (MessageLogged $event) use (&$entries) {
        $entries[] = [
            'level'   => $event->level,
            'message' => $event->message,
            'context' => $event->context,
        ];
    });

    return $entries;   // NOTE: entries are captured by reference inside the closure only
}

/**
 * Bind a ManifestCache that always throws so the 'error' path is triggered.
 */
function bindFailingCacheForLogging(): void
{
    /** @var ImageProcessor $processor */
    $processor = app()->make(ImageProcessor::class);
    $outputDir = $processor->normalizeOutputDir(config('media-toolkit.output_dir', 'media/optimized'));

    $failing = new class (
        public_path(),
        $outputDir,
        [0.5, 0.75, 1.0, 1.5, 2.0],
        100,
        $processor,
    ) extends \Laraextend\MediaToolkit\Cache\ManifestCache {
        public function getOrCreate(
            string $sourcePath,
            int    $sourceModified,
            ?int   $displayWidth,
            string $format,
            bool   $singleOnly,
            array  $operations,
            string $operationsFingerprint,
            int    $quality,
            bool   $noCache = false,
        ): array {
            throw new \RuntimeException('Forced cache failure for logging test.');
        }
    };

    app()->instance(\Laraextend\MediaToolkit\Cache\ManifestCache::class, $failing);
}

/**
 * Create a bypassing ImageProcessor (simulates GD memory-limit bypass).
 */
function makeBypassingProcessorForLogging(): ImageProcessor
{
    $driverName = extension_loaded('imagick') ? 'imagick' : 'gd';
    $driver     = $driverName === 'imagick' ? new ImagickDriver() : new GdDriver();

    return new class ($driverName, new ImageManager($driver)) extends ImageProcessor {
        public function shouldBypassOptimization(string $sourcePath, ?int $targetWidth, ?int $targetHeight): bool
        {
            return true;
        }
    };
}

// ─────────────────────────────────────────────────────────────
//  LOGGING TESTS — use Log::listen() to capture entries
// ─────────────────────────────────────────────────────────────

test('on_not_found logs a warning via Log facade', function (): void {
    $logged = [];
    Log::listen(function (MessageLogged $event) use (&$logged) {
        $logged[] = ['level' => $event->level, 'message' => $event->message];
    });

    Media::image('non/existent/path.jpg')->html(alt: 'Test');

    $match = collect($logged)->first(fn ($e) =>
        $e['level'] === 'warning' && str_contains($e['message'], 'not_found')
    );

    expect($match)->not->toBeNull();
    expect($match['message'])->toContain('non/existent/path.jpg');
});

test('on_error logs an error via Log facade', function (): void {
    $logged = [];
    Log::listen(function (MessageLogged $event) use (&$logged) {
        $logged[] = ['level' => $event->level, 'message' => $event->message];
    });

    bindFailingCacheForLogging();

    Media::image($this->landscapeImage)->resize(width: 300)->html(alt: 'Test');

    $match = collect($logged)->first(fn ($e) =>
        $e['level'] === 'error' && str_contains($e['message'], 'error')
    );

    expect($match)->not->toBeNull();
    expect($match['message'])->toContain($this->landscapeImage);
});

test('on_memory_limit logs a notice via Log facade', function (): void {
    $logged = [];
    Log::listen(function (MessageLogged $event) use (&$logged) {
        $logged[] = ['level' => $event->level, 'message' => $event->message];
    });

    $bypassingProcessor = makeBypassingProcessorForLogging();
    app()->instance(ImageProcessor::class, $bypassingProcessor);

    Media::image($this->landscapeImage)->resize(width: 300)->html(alt: 'Test');

    $match = collect($logged)->first(fn ($e) =>
        $e['level'] === 'notice' && str_contains($e['message'], 'memory_limit')
    );

    expect($match)->not->toBeNull();
    expect($match['message'])->toContain($this->landscapeImage);
});

test('logging disabled skips log calls', function (): void {
    $logged = [];
    Log::listen(function (MessageLogged $event) use (&$logged) {
        $logged[] = $event;
    });

    $this->setPackageConfig([
        'image' => [
            'logging' => ['enabled' => false],
        ],
    ]);

    Media::image('non/existent/path.jpg')->html(alt: 'Test');

    // No log entries should have been written by our package
    $packageLogs = collect($logged)->filter(fn ($e) =>
        str_contains($e->message, '[media-toolkit]')
    );

    expect($packageLogs)->toBeEmpty();
});

// ─────────────────────────────────────────────────────────────
//  FAILURE REGISTRY TESTS
// ─────────────────────────────────────────────────────────────

test('failure registry records a not_found entry', function (): void {
    $registry = makeTestRegistry();
    app()->instance(FailureRegistry::class, $registry);

    // Suppress log output to real log channel during test
    Log::listen(function (MessageLogged $event) {});

    Media::image('missing/image.jpg')->html(alt: 'Test');

    $all = $registry->all();

    expect($all)->toHaveKey('missing/image.jpg');
    expect($all['missing/image.jpg']['reason'])->toBe('not_found');
    expect($all['missing/image.jpg']['count'])->toBe(1);
    expect($all['missing/image.jpg']['params'])->toBeEmpty();
});

test('failure registry increments count on repeated failures', function (): void {
    $registry = makeTestRegistry();
    app()->instance(FailureRegistry::class, $registry);

    Log::listen(function (MessageLogged $event) {});

    Media::image('missing/image.jpg')->html(alt: 'Test');
    Media::image('missing/image.jpg')->html(alt: 'Test');
    Media::image('missing/image.jpg')->html(alt: 'Test');

    expect($registry->all()['missing/image.jpg']['count'])->toBe(3);
});

test('failure registry records memory_limit entry with params', function (): void {
    $registry = makeTestRegistry();
    app()->instance(FailureRegistry::class, $registry);

    $bypassingProcessor = makeBypassingProcessorForLogging();
    app()->instance(ImageProcessor::class, $bypassingProcessor);

    Log::listen(function (MessageLogged $event) {});

    Media::image($this->landscapeImage)->resize(width: 400)->html(alt: 'Test');

    $all = $registry->all();

    expect($all)->toHaveKey($this->landscapeImage);

    $entry = $all[$this->landscapeImage];
    expect($entry['reason'])->toBe('memory_limit');
    expect($entry['params'])->toHaveKey('display_width');
    expect($entry['params'])->toHaveKey('format');
    expect($entry['params'])->toHaveKey('quality');
    expect($entry['params'])->toHaveKey('operations_fingerprint');
    expect($entry['params']['display_width'])->toBe(400);
});

test('failure registry records error entry with params', function (): void {
    $registry = makeTestRegistry();
    app()->instance(FailureRegistry::class, $registry);

    Log::listen(function (MessageLogged $event) {});

    bindFailingCacheForLogging();

    Media::image($this->landscapeImage)->resize(width: 300)->html(alt: 'Test');

    $all = $registry->all();

    expect($all)->toHaveKey($this->landscapeImage);

    $entry = $all[$this->landscapeImage];
    expect($entry['reason'])->toBe('error');
    expect($entry['params'])->toHaveKey('format');
    expect($entry['params']['display_width'])->toBe(300);
});

test('failure registry remove() deletes a single entry', function (): void {
    $registry = makeTestRegistry();
    $registry->record('path/a.jpg', 'not_found');
    $registry->record('path/b.jpg', 'not_found');

    expect($registry->count())->toBe(2);

    $registry->remove('path/a.jpg');

    expect($registry->count())->toBe(1);
    expect($registry->all())->not->toHaveKey('path/a.jpg');
    expect($registry->all())->toHaveKey('path/b.jpg');
});

test('failure registry clear() empties all entries', function (): void {
    $registry = makeTestRegistry();
    $registry->record('path/a.jpg', 'not_found');
    $registry->record('path/b.jpg', 'error', ['format' => 'webp']);

    $registry->clear();

    expect($registry->count())->toBe(0);
    expect($registry->all())->toBeEmpty();
});

// ─────────────────────────────────────────────────────────────
//  media:process-pending COMMAND TESTS
// ─────────────────────────────────────────────────────────────

test('media:process-pending is registered as an artisan command', function (): void {
    expect(Artisan::all())->toHaveKey('media:process-pending');
});

test('media:process-pending --list shows table of entries', function (): void {
    $registry = makeTestRegistry();
    $registry->record('some/image.jpg', 'memory_limit', [
        'display_width' => 400, 'format' => 'webp', 'quality' => 80,
        'operations_fingerprint' => md5(''), 'single_only' => true,
    ]);

    app()->instance(FailureRegistry::class, $registry);

    $exitCode = Artisan::call('media:process-pending', ['--list' => true]);
    $output   = Artisan::output();

    expect($exitCode)->toBe(0);
    expect($output)->toContain('some/image.jpg');
    expect($output)->toContain('memory_limit');
});

test('media:process-pending --list with no entries shows informational message', function (): void {
    $registry = makeTestRegistry();
    app()->instance(FailureRegistry::class, $registry);

    $exitCode = Artisan::call('media:process-pending', ['--list' => true]);

    expect($exitCode)->toBe(0);
    expect(Artisan::output())->toContain('No pending failures');
});

test('media:process-pending --clear empties the registry', function (): void {
    $registry = makeTestRegistry();
    $registry->record('old/image.jpg', 'not_found');
    $registry->record('another/image.jpg', 'error', ['format' => 'webp', 'quality' => 80]);

    app()->instance(FailureRegistry::class, $registry);

    $exitCode = Artisan::call('media:process-pending', ['--clear' => true]);

    expect($exitCode)->toBe(0);
    expect(Artisan::output())->toContain('Cleared');
    expect($registry->count())->toBe(0);
});

test('media:process-pending processes a real pending memory_limit entry', function (): void {
    $registry = makeTestRegistry();
    $registry->record($this->landscapeImage, 'memory_limit', [
        'display_width'          => 300,
        'format'                 => 'jpg',
        'quality'                => 82,
        'operations_fingerprint' => md5(''),
        'single_only'            => true,
    ]);

    app()->instance(FailureRegistry::class, $registry);

    $exitCode = Artisan::call('media:process-pending');

    expect($exitCode)->toBe(0);
    expect(Artisan::output())->toContain('processed');
    // Entry should be removed after success
    expect($registry->all())->not->toHaveKey($this->landscapeImage);
});

test('media:process-pending skips entries whose source file is still missing', function (): void {
    $registry = makeTestRegistry();
    $registry->record('truly/missing.jpg', 'not_found');

    app()->instance(FailureRegistry::class, $registry);

    $exitCode = Artisan::call('media:process-pending');

    expect($exitCode)->toBe(0);
    expect(Artisan::output())->toContain('still not found');
    // Entry stays in registry because file is still missing
    expect($registry->all())->toHaveKey('truly/missing.jpg');
});
