<?php

namespace VmEngine\Synapse;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

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
        Schema::defaultStringLength(191);
        bcscale(8);

        if (app()->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/synapse.php' => config_path('synapse.php'),
                __DIR__.'/../config/synapps.php' => config_path('synapps.php'),
            ], 'config');
        } // else {
        // }
        // Publish config, migrations, views, etc.
        // $this->publishes([
        //     __DIR__.'/path/to/config/synapse.php' => config_path('synapse.php'),
        // ], 'config');

        // $this->loadMigrationsFrom(__DIR__.'/path/to/migrations');
        // $this->loadViewsFrom(__DIR__.'/path/to/views', 'synapse');
        // $this->loadRoutesFrom(__DIR__.'/path/to/routes/web.php');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Register bindings, singletons, etc.
        // $this->app->singleton(Synapse::class, function ($app) {
        //     return new Synapse();
        // });
        if (config('synapps.binding.alias')) {
            $this->app->booting(function () {
                $loader = \Illuminate\Foundation\AliasLoader::getInstance();
                $classList = config('synapps.binding.alias');
                foreach ($classList as $classAliasName => $className) {
                    $loader->alias($classAliasName, $className);
                }
            });
        }

        if (config('synapps.system.public_path')) {
            $this->app->bind('path.public', function () {
                return realpath(base_path(config('synapps.system.public_path')));
            });
        }

        foreach (config('synapps.binding.interface', []) as $contract => $service) {
            $this->app->bind(
                $contract,
                $service
            );
        }

        foreach (config('synapps.binding.class', []) as $class => $newClass) {
            $this->app->bind($class, function ($app, $args) use ($newClass) {
                if (empty($args)) {
                    return new $newClass;
                }

                return new $newClass(...$args);
            });
        }
    }
}
