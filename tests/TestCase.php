<?php

namespace Laraexten\ImageOptimizer\Test;

use Laraexten\ImageOptimizer\ImageOptimizerServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ImageOptimizerServiceProvider::class,
        ];
    }
}
