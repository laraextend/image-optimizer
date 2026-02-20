<?php

namespace Laraexten\ImageOptimizer;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Laraexten\ImageOptimizer\Helpers\ImageOptimizer;

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
        $this->publishes([
            __DIR__.'/../config/image-optimizer.php' => config_path('image-optimizer.php'),
        ], 'image-optimizer-config');

        Blade::componentNamespace('Laraexten\\ImageOptimizer\\Components', 'laraexten');

        // Globalen Helper registrieren
        require_once __DIR__.'/Helpers/img_helper.php';
    }
}
