<?php

namespace Laraextend\ImageOptimizer\Tests;

use Illuminate\Support\Facades\File;
use Laraextend\ImageOptimizer\Helpers\ImageOptimizer;
use Laraextend\ImageOptimizer\ImageOptimizerServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected string $fixturesRoot = 'tests/fixtures/image-optimizer';

    protected string $landscapeImage = 'tests/fixtures/image-optimizer/landscape.jpg';

    protected string $portraitImage = 'tests/fixtures/image-optimizer/portrait.jpg';

    protected string $squareImage = 'tests/fixtures/image-optimizer/square.jpg';

    protected function getPackageProviders($app): array
    {
        return [
            ImageOptimizerServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('app.env', 'local');
        $app['config']->set('app.url', 'http://localhost');
    }

    protected function setUp(): void
    {
        parent::setUp();

        config(['image-optimizer' => $this->defaultConfig()]);
        $this->app->forgetInstance(ImageOptimizer::class);

        $this->prepareFixtureImages();
        $this->cleanOutputDirectories();
    }

    protected function tearDown(): void
    {
        $this->cleanOutputDirectories();
        parent::tearDown();
    }

    protected function defaultConfig(): array
    {
        return require dirname(__DIR__).'/config/image-optimizer.php';
    }

    protected function setPackageConfig(array $overrides): ImageOptimizer
    {
        $merged = array_replace_recursive($this->defaultConfig(), $overrides);
        config(['image-optimizer' => $merged]);
        $this->app->forgetInstance(ImageOptimizer::class);

        return $this->app->make(ImageOptimizer::class);
    }

    protected function optimizer(): ImageOptimizer
    {
        return $this->app->make(ImageOptimizer::class);
    }

    protected function cleanOutputDirectories(): void
    {
        $configuredDir = config('image-optimizer.output_dir', 'img/optimized');

        File::deleteDirectory(public_path('img/optimized'));
        File::deleteDirectory(public_path($configuredDir));
        File::deleteDirectory(public_path('custom/optimized'));
    }

    protected function prepareFixtureImages(): void
    {
        $dir = base_path($this->fixturesRoot);
        File::ensureDirectoryExists($dir, 0755, true);

        $this->createTestImage(base_path($this->landscapeImage), 800, 400);
        $this->createTestImage(base_path($this->portraitImage), 400, 800);
        $this->createTestImage(base_path($this->squareImage), 300, 300);
    }

    protected function createTestImage(string $path, int $width, int $height): void
    {
        if (function_exists('imagecreatetruecolor') && function_exists('imagejpeg')) {
            $image = imagecreatetruecolor($width, $height);
            $background = imagecolorallocate($image, 120, 160, 210);
            imagefill($image, 0, 0, $background);
            imagejpeg($image, $path, 90);
            imagedestroy($image);

            return;
        }

        if (class_exists(\Imagick::class)) {
            $image = new \Imagick;
            $image->newImage($width, $height, new \ImagickPixel('rgb(120,160,210)'), 'jpg');
            $image->setImageFormat('jpg');
            $image->writeImage($path);
            $image->clear();

            return;
        }

        $this->markTestSkipped('Neither GD nor Imagick is available to create fixture images.');
    }

    protected function assertGeneratedPublicFileExists(string $html): void
    {
        preg_match('/\ssrc="([^"]+)"/', $html, $matches);
        $this->assertNotEmpty($matches[1] ?? null, 'No src attribute found in rendered HTML.');

        $path = parse_url($matches[1], PHP_URL_PATH);
        $this->assertNotFalse($path);
        $this->assertNotNull($path);
        $this->assertTrue(str_starts_with($path, '/'));

        $absolute = public_path(ltrim($path, '/'));
        $this->assertFileExists($absolute);
    }
}
