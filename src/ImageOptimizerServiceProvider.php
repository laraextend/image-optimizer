<?php

namespace XXXX\XXXX;

use App\Helpers\ImageOptimizer;
use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
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
        require_once app_path('Helpers/img_helper.php');
    }
}
