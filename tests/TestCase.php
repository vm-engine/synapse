<?php

declare(strict_types=1);

namespace Tests;

use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use VmEngine\Synapse\Facades\DbConfig;
use VmEngine\Synapse\Facades\DbLang;
use VmEngine\Synapse\SynapseServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * Get package providers.
     */
    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            SynapseServiceProvider::class,
        ];
    }

    /**
     * Get package aliases.
     */
    protected function getPackageAliases($app): array
    {
        return [
            'DbConfig' => DbConfig::class,
            'DbLang' => DbLang::class,
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
     * Register the package's own Blade views under the "synapse" namespace.
     *
     * In a full application this is wired up by SynapseConfigGenerator via
     * config('synapse.view_path.general'), which only exists once SynApps
     * config has been generated. Isolated package tests never generate that
     * config, so components calling view('synapse::components.*') in
     * render() would otherwise fail to resolve the namespace.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->app['view']->addNamespace('synapse', __DIR__.'/../resources/views');
    }

    /**
     * Define database migrations.
     */
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
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
