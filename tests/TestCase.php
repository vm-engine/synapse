<?php

namespace Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use VmEngine\Synapse\SynapseServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * Get package providers.
     */
    protected function getPackageProviders($app): array
    {
        return [
            SynapseServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     */
    protected function defineEnvironment($app): void
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    /**
     * Get application base path.
     */
    protected function getBasePath(): string
    {
        // Use a temp directory for test base path
        return __DIR__.'/../vendor/orchestra/testbench-core/laravel';
    }
}
