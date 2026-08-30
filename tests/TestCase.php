<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use LazilyRefreshDatabase;

    public function createApplication()
    {
        $app = require Application::inferBasePath().'/bootstrap/app.php';

        // Production config is cached, so phpunit.xml VIEW_COMPILED_PATH never
        // reaches config('view.compiled'). Override after config loads so tests
        // do not write root-owned compiled views into the live php-fpm path.
        $app->afterBootstrapping(LoadConfiguration::class, function (Application $app): void {
            $app['config']->set('view.compiled', storage_path('framework/testing/views'));
        });

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
