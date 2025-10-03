<?php

namespace VmEngine\Synapse;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\ViewServiceProvider;
use VmEngine\Synapse\Components\Drawer;
use VmEngine\Synapse\Components\Password;
use VmEngine\Synapse\Components\Select;
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
        $this->registerSynapseComponents();
    }

    public function boot() {}

    private function registerSynapseComponents()
    {
        Blade::component(Drawer::class, 'synapse-drawer');
        Blade::component(Select::class, 'synapse-select');
        Blade::component(Password::class, 'synapse-password');
    }
}
