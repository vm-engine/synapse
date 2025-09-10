<?php

namespace VmEngine\Synapse\Services\ConfigGenerator\Builder;

use VmEngine\Synapse\Services\ConfigGenerator\BuilderBase;

class Modules extends BuilderBase
{
    protected $moduleString;
    protected $tmpModulesDev;

    protected $configAppOverride;
    protected $configDevOverride;

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
                file_get_contents($modulePath),
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
        if (!file_exists(synapps_path('config/modules.json'))) {
            file_put_contents(synapps_path('config/modules.json'), "{}");
        }

        if (!file_exists(synapps_path('config/modules.dev.json'))) {
            file_put_contents(synapps_path('config/modules.dev.json'), "{}");
        }

        $this->configAppOverride = $this->sanitizeConfigOverrides(json_decode(
            file_get_contents(synapps_path('config/modules.json')),
            true
        ));

        $this->configDevOverride = $this->sanitizeConfigOverrides(json_decode(
            file_get_contents(synapps_path('config/modules.dev.json')),
            true
        ));
    }

    private function sanitizeConfigOverrides($configs)
    {
        $protectedKeys = $this->synapps->mainConfig['protected_module_keys'];
        foreach ($configs as $id => $config) {
            $filteredConfig = array_filter($config, function ($v, $k) use ($protectedKeys) {
                return !in_array($k, $protectedKeys);
            }, ARRAY_FILTER_USE_BOTH);
            $configs[$id] = $filteredConfig;
        }

        return $config;
    }

    private function mergeConfigs()
    {
        $this->synapps->config['modules'] = array_replace_recursive(
            $this->synapps->config['modules'],
            $this->configAppOverride,
            $this->configDevOverride
        );
    }

    public function finalize()
    {
        file_put_contents(
            synapps_path('config/compiled/modules.json'),
            json_encode($this->synapps->config['modules'])
        );
    }
}
