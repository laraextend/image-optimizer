<?php

namespace App\Providers;

use App\Helpers\ImageOptimizer;
use Illuminate\Support\ServiceProvider;

class ImageOptimizerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ImageOptimizer::class);
    }

    public function boot(): void
    {
        // Globalen Helper registrieren
        require_once app_path('Helpers/img_helper.php');
    }
}
