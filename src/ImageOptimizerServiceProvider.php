<?php

namespace Laraexten\ImageOptimizer;

use Laraexten\ImageOptimizer\Helpers\ImageOptimizer;
use Illuminate\Support\ServiceProvider;

class ImageOptimizerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {

        //
        $this->app->singleton(ImageOptimizer::class);

    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {

        // Globalen Helper registrieren
        require_once __DIR__ . '/Helpers/img_helper.php';
    }
}
