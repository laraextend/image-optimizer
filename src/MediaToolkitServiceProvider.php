<?php

namespace Laraextend\MediaToolkit;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Laraextend\MediaToolkit\Console\Commands\ImageCacheClear;
use Laraextend\MediaToolkit\Console\Commands\ImageCacheWarm;
use Laraextend\MediaToolkit\Helpers\ImageOptimizer;

class MediaToolkitServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/media-toolkit.php', 'media-toolkit');

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
                __DIR__.'/../config/media-toolkit.php' => config_path('media-toolkit.php'),
            ], 'media-toolkit-config');
        }

        Blade::componentNamespace('Laraextend\\MediaToolkit\\Components', 'laraextend');

        require_once __DIR__.'/Helpers/img_helper.php';
    }
}
