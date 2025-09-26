<?php

namespace VmEngine\Synapse;

use Illuminate\Pagination\Paginator;
use Illuminate\View\ViewServiceProvider;
use VmEngine\Synapse\Services\SynapseUtils;

class SynapseViewServiceProvider extends ViewServiceProvider
{
    public function register()
    {
        if (SynapseUtils::isSynAppsAutoloaded()) {
            foreach (config('synapse.view_path.general', []) as $path) {
                $this->loadViewsFrom($path[0], $path[1]);
            }
        }
        parent::register();
        if (config('livewire.layout') && SynapseUtils::isSynAppsAutoloaded()) {
            config(['livewire.layout' => 'synapps::components.layouts.app']);
        }
        if (config('livewire.pagination_theme')) {
            config(['livewire.pagination_theme' => 'bootstrap']);
        }
    }

    public function boot()
    {
        Paginator::useBootstrapFive();
    }
}
