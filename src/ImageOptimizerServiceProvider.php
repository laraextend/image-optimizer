<?php

namespace Laraextend\ImageOptimizer;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Laraextend\ImageOptimizer\Console\Commands\ImageCacheClear;
use Laraextend\ImageOptimizer\Console\Commands\ImageCacheWarm;
use Laraextend\ImageOptimizer\Helpers\ImageOptimizer;

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

        Blade::componentNamespace('Laraextend\\ImageOptimizer\\Components', 'laraextend');

        require_once __DIR__.'/Helpers/img_helper.php';
    }
}
