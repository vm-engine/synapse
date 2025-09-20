<?php

namespace VmEngine\Synapse;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use VmEngine\Synapse\Console\Commands\SynAppsDiscover;

/**
 *  @SuppressWarnings(PHPMD.StaticAccess)
 */
class SynapseServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/synapse.php' => config_path('synapse.php'),
                __DIR__.'/../config/synapps.php' => config_path('synapps.php'),
            ], 'synapse-config');

            $this->bootDbOverride();

            $this->commands([
                SynAppsDiscover::class
            ]);
        }

        Schema::defaultStringLength(191);
        bcscale(8);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->bindAlias();
        $this->bindPublicPath();
        $this->bindInterface();
        $this->bindClass();
        $this->registerServiceProviders();
    }

    private function  bindAlias()
    {
        if (config('synapps.binding.alias')) {
            $this->app->booting(function () {
                $loader = \Illuminate\Foundation\AliasLoader::getInstance();
                $classList = config('synapps.binding.alias');
                foreach ($classList as $classAliasName => $className) {
                    $loader->alias($classAliasName, $className);
                }
            });
        }
    }

    private function bindPublicPath()
    {
        if (config('synapps.system.public_path')) {
            $this->app->bind('path.public', function () {
                return realpath(base_path(config('synapps.system.public_path')));
            });
        }
    }

    private function bindInterface()
    {
        foreach (config('synapse.binding.interface', []) as $contract => $service) {
            $this->app->bind(
                $contract,
                $service
            );
        }
    }

    private function bindClass()
    {
        foreach (config('synapps.binding.class', []) as $class => $newClass) {
            $this->app->bind($class, function ($app, $args) use ($newClass) {
                if (empty($args)) {
                    return new $newClass;
                }

                return new $newClass(...$args);
            });
        }
    }

    private function registerServiceProviders()
    {
        $moduleConfigs = config('synapps.modules');

        foreach ($moduleConfigs as $moduleConfig) {
            // $serviceProviderClass 
        }
    }

    private function bootDbOverride()
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(config('synapse.migration_path'));
            $this->callAfterResolving(DatabaseSeeder::class, function ($seeder) {
                $seedList = config('synapse.seed_path');
                foreach ($seedList as $seederFile) {
                    $seederClassList = include $seederFile;
                    array_walk($seederClassList, function ($seederClass) use ($seeder) {

                        $seeder->call($seederClass);
                    });
                }
            });
        }
    }
}
