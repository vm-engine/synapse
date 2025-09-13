<?php

namespace VmEngine\Synapse;

use Illuminate\View\ViewServiceProvider;

class SynapseViewServiceProvder extends ViewServiceProvider
{
    public function register()
    {
        $this->app['config']['view.paths'] = config('synapse.view_path.general');
        parent::register();
    }
}
