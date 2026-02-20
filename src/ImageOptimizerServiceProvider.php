<?php

namespace Laraexten\ImageOptimizer;

use Laraexten\ImageOptimizer\Console\Commands\ImageCacheClear;
use Laraexten\ImageOptimizer\Console\Commands\ImageCacheWarm;
use Laraexten\ImageOptimizer\Helpers\ImageOptimizer;
use Illuminate\Support\ServiceProvider;

class ImageOptimizerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/image-optimizer.php', 'image-optimizer');
        $this->app->singleton(ImageOptimizer::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ImageCacheClear::class,
                ImageCacheWarm::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/image-optimizer.php' => config_path('image-optimizer.php'),
            ], 'image-optimizer-config');
        }

        // Globalen Helper registrieren
        require_once __DIR__.'/Helpers/img_helper.php';
    }
}
