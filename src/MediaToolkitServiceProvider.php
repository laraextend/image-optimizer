<?php

namespace Laraextend\MediaToolkit;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Laraextend\MediaToolkit\Builders\ImageBuilder;
use Laraextend\MediaToolkit\Cache\ManifestCache;
use Laraextend\MediaToolkit\Console\Commands\CacheClear;
use Laraextend\MediaToolkit\Console\Commands\CacheWarm;
use Laraextend\MediaToolkit\Processing\ImageProcessor;
use Laraextend\MediaToolkit\Rendering\ImageHtmlRenderer;

class MediaToolkitServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/media-toolkit.php', 'media-toolkit');

        // ── Core singletons ────────────────────────────────────────────────

        $this->app->singleton(ImageProcessor::class, function (): ImageProcessor {
            return ImageProcessor::make(config('media-toolkit.driver', 'auto'));
        });

        $this->app->singleton(ManifestCache::class, function (Application $app): ManifestCache {
            /** @var ImageProcessor $processor */
            $processor = $app->make(ImageProcessor::class);
            $outputDir = $processor->normalizeOutputDir(
                config('media-toolkit.output_dir', 'media/optimized')
            );

            return new ManifestCache(
                publicPath:  public_path(),
                outputDir:   $outputDir,
                sizeFactors: (array) config('media-toolkit.image.responsive.size_factors', [0.5, 0.75, 1.0, 1.5, 2.0]),
                minWidth:    (int)   config('media-toolkit.image.responsive.min_width', 100),
                processor:   $processor,
            );
        });

        $this->app->singleton(ImageHtmlRenderer::class);

        // ── 'media-toolkit' facade accessor ────────────────────────────────
        // Resolves to a lightweight factory object whose image() method
        // returns a fresh ImageBuilder per call.

        $this->app->bind('media-toolkit', function (Application $app): object {
            return new class ($app) {
                public function __construct(private readonly Application $app) {}

                public function image(string $path): ImageBuilder
                {
                    return new ImageBuilder(
                        path:      $path,
                        processor: $this->app->make(ImageProcessor::class),
                        cache:     $this->app->make(ManifestCache::class),
                        renderer:  $this->app->make(ImageHtmlRenderer::class),
                    );
                }
            };
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CacheClear::class,
                CacheWarm::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/media-toolkit.php' => config_path('media-toolkit.php'),
            ], 'media-toolkit-config');
        }

        Blade::componentNamespace('Laraextend\\MediaToolkit\\Components\\Image', 'media');

        require_once __DIR__.'/Helpers/media_helper.php';
    }
}
