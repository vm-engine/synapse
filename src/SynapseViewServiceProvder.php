<?php

namespace VmEngine\Synapse;

use Illuminate\View\ViewServiceProvider;

class SynapseViewServiceProvder extends ViewServiceProvider
{
    public function register()
    {
        // $this->app['config']['view.paths'] = config('synapse.view_path.general');
        foreach (config('synapse.view_path.general') as $path) {
            $this->loadViewsFrom($path[0], $path[1]);
        }
        parent::register();
    }
}
