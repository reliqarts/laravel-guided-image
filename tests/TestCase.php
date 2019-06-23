<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Tests;

use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as TestbenchTestCase;
use ReliqArts\GuidedImage\ServiceProvider;

abstract class TestCase extends TestbenchTestCase
{
    /**
     * @param Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        // set base path
        $app->setBasePath(__DIR__ . '/..');

        // set app config
        $app['config']->set('database.default', 'testing');
    }

    /**
     * @param Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            ServiceProvider::class,
        ];
    }
}
