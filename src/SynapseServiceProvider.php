<?php

namespace VmEngine\Synapse;

use Illuminate\Support\ServiceProvider;

class SynapseServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot()
    {
        if (app()->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/synapse.php' => config_path('synapse.php'),
                __DIR__ . '/../config/synapps.php' => config_path('synapps.php'),
            ], 'config');
        } else {
            // $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
            // $this->loadViewsFrom(__DIR__ . '/../resources/views', 'synapse');
        }
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
    }
}
