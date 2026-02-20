<?php

namespace Laraextend\ImageOptimizer;

<<<<<<< Updated upstream
<<<<<<< HEAD
use Laraexten\ImageOptimizer\Console\Commands\ImageCacheClear;
use Laraexten\ImageOptimizer\Console\Commands\ImageCacheWarm;
use Laraexten\ImageOptimizer\Helpers\ImageOptimizer;
=======
use Illuminate\Support\Facades\Blade;
>>>>>>> claude/heuristic-wilson
use Illuminate\Support\ServiceProvider;
use Laraexten\ImageOptimizer\Helpers\ImageOptimizer;
=======
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Laraextend\ImageOptimizer\Console\Commands\ImageCacheClear;
use Laraextend\ImageOptimizer\Console\Commands\ImageCacheWarm;
use Laraextend\ImageOptimizer\Helpers\ImageOptimizer;
>>>>>>> Stashed changes

class ImageOptimizerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/image-optimizer.php', 'image-optimizer');
<<<<<<< HEAD
=======

>>>>>>> claude/heuristic-wilson
        $this->app->singleton(ImageOptimizer::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
<<<<<<< HEAD
        if ($this->app->runningInConsole()) {
            $this->commands([
                ImageCacheClear::class,
                ImageCacheWarm::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/image-optimizer.php' => config_path('image-optimizer.php'),
            ], 'image-optimizer-config');
        }
=======
        $this->publishes([
            __DIR__.'/../config/image-optimizer.php' => config_path('image-optimizer.php'),
        ], 'image-optimizer-config');

        Blade::componentNamespace('Laraexten\\ImageOptimizer\\Components', 'laraexten');
>>>>>>> claude/heuristic-wilson

        Blade::componentNamespace('Laraextend\\ImageOptimizer\\Components', 'laraextend');

        // Globalen Helper registrieren
        require_once __DIR__.'/Helpers/img_helper.php';
    }
}
