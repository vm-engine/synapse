<?php

namespace VmEngine\Synapse;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use VmEngine\Synapse\Services\SynapseUtils;

class SynapseRouteServiceProvider extends RouteServiceProvider
{
    public function boot() {}

    public function register()
    {
        parent::register();
    }

    public function map()
    {
        if (SynapseUtils::isSynAppsAutoloaded()) {
            $this->registerRoutes();
        }
    }

    protected function registerRoutes()
    {
        $modulePaths = config('synapse.module_path.general', []);

        $urlPrefix = config('synapps.apps.urls.prefix', '/');
        $apiPrefix = config('synapps.apps.urls.api_prefix', '/api');

        Route::middleware('web')
            ->prefix($urlPrefix)
            ->group(base_path('routes/web.php'));

        if (file_exists(base_path('routes/api.php'))) {
            Route::prefix($apiPrefix)
                ->middleware('api')
                ->group(base_path('routes/api.php'));
        }

        foreach ($modulePaths as $id => $path) {
            $modulePrefix = config(
                'synapps.modules.'.$id.'.url_prefix',
                ($id == 'home' ? '' : Str::slug($id, '-'))
            );
            $routesPath = rtrim($path, DS).DS.'routes'.DS;
            Route::middleware('web')
                ->name($id.'.')
                ->prefix($urlPrefix.'/'.$modulePrefix)
                ->group($routesPath.'web.php');

            // this route will not have url prefixed, used to override global path
            if (file_exists($routesPath.'web.override.php')) {
                Route::middleware('web')
                    ->name($id.'.')
                    ->group($routesPath.'web.override.php');
            }

            if (file_exists($routesPath.'api.php')) {
                Route::middleware('api')
                    ->name('api.'.$id.'.')
                    ->prefix($apiPrefix.'/'.$modulePrefix)
                    ->group($routesPath.'api.php');
            }
        }
    }
}