<?php

namespace VmEngine\Synapse\Services\ConfigGenerator\Builder;

use VmEngine\Synapse\Services\ConfigGenerator\BuilderBase;

class Modules extends BuilderBase
{
    protected $moduleString;
    protected $tmpModulesDev;

    protected $newModules;
    protected $newModulesDev;

    public function init()
    {
        $this->loadModulePackageConfig();
        $this->loadSynAppsModuleConfig();
        $this->mergeConfigs();
    }

    private function loadModulePackageConfig()
    {
        $this->synapps->modules = array_merge(
            glob(synapps_path('modules/*/module.json')),
            glob(base_path('vendor/*/synmod-*/module.json')),
            glob(base_path('vendor/*/synmodule-*/module.json')),
            glob(base_path('vendor/*/synapps-*/module.json')),
        );

        foreach ($this->synapps->modules as $modulePath) {
            $moduleConfig = json_decode(
                file_get_contents($path),
                true
            );
            if (!isset($moduleConfig['load_priority'])) {
                $moduleConfig['load_priority'] = 99;
            }

            $this->synapps->config['modules'][$moduleConfig['id']] = $moduleConfig;
            $this->synapps->moduleDir[dirname($modulePath)] = $moduleConfig['id'];
        }

        $moduleSorted = collect($this->synapps->config['modules'])->sortBy('load_priority');
        $this->synapps->config['modules'] = $moduleSorted;

    }

    private function loadSynAppsModuleConfig()
    {
        $configCompiled = '';
        if (file_exists(synapps_path('config/compiled/modules.json'))) {
            $configCompiled = file_get_contents(synapps_path('config/compiled/modules.json'));
        }

        $configCurrent = json_decode($this->synapps->config['modules']);

        if ($configCompiled != $configCurrent) {
            file_put_contents(synapps_path('config/compiled/modules.json'), $configCurrent);
        }

        if (!file_exists(synapps_path('config/compiled/modules.dev.json'))) {
            file_put_contents(synapps_path('config/compiled/modules.dev.json', "{}"));
        }

        $this->moduleString = file_get_contents(synapps_path('config/compiled/modules.dev.json'));
        $this->tmpModulesDev = json_decode($this->moduleString, true);
    }

    private function mergeConfigs()
    {

    }
}
