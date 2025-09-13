<?php

namespace VmEngine\Synapse;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Route;

class SynapseRouteServiceProvider extends RouteServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(config('synapse.migration_path'));
        }
    }

    public function register()
    {
        parent::register();
    }

    public function map()
    {
        $this->registerControllerNamespace();
    }

    /**
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    protected function registerControllerNamespace()
    {
        $controllerPaths = config('synapse.module_path.general', []);

        $routeModuleException = config('synapps.system.routes.module_exception', []);

        $fileNames = [
            'api' => true,
            'web' => false,
        ];
        foreach ($controllerPaths as $namespace => $pathToModule) {
            $moduleConfig = config('synapps.modules.'.$namespace);

            $moduleNamespace = explode('\\', trim($namespace, '\\'));
            $moduleNamespace = array_pop($moduleNamespace);

            $namespace .= 'Controllers';

            foreach ($fileNames as $fileName => $isApi) {
                $path = sprintf('%s'.DS.'%s.php', $pathToModule, $fileName);

                $pathBinding = config(
                    'synapps.binding.route.'.$moduleNamespace.'.'.($isApi ? 'api' : 'web'),
                    false
                );
                if ($pathBinding) {
                    $pathBinding = synapps_path($pathBinding);
                    if (file_exists($pathBinding)) {
                        Route::middleware($isApi ? ['api'] : ['web'])
                            ->prefix(
                                $isApi && $moduleNamespace != 'moduser'
                                    ? config('synapps.endpoint.laravel.api.'.$moduleNamespace)
                                    : ''
                            )
                            ->namespace($namespace)
                            ->group($pathBinding);
                    }
                }

                if (! file_exists($path)) {
                    continue;
                }

                if (in_array($moduleNamespace, $routeModuleException)) {
                    // register router utama per module
                    Route::namespace($namespace)
                        ->group($path);

                } else {
                    Route::middleware($isApi ? ['api'] : ['web'])
                        ->prefix(
                            $isApi && $moduleNamespace != 'moduser'
                                ? config('synapps.endpoint.laravel.api.'.$moduleNamespace)
                                : ''
                        )
                        ->namespace($namespace)
                        ->group($path);
                }
            }
        }
        // dd($controllerPaths);

        $this->mapApiRoutes();
        $this->mapWebRoutes();
    }
}
