<?php

namespace VmEngine\Synapse\Services\ConfigGenerator\Builder;

use Illuminate\Support\Str;
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
        $modules = array_merge(
            glob(synapps_path('modules/*/module.json')),
            glob(base_path('vendor/*/synmod-*/module.json')),
            glob(base_path('vendor/*/synmodule-*/module.json')),
            glob(base_path('vendor/*/synapps-*/module.json')),
        );

        foreach ($modules as $modulePath) {
            $moduleConfig = json_decode(
                file_get_contents($modulePath),
                true
            );

            if (! isset($moduleConfig['load_priority'])) {
                $moduleConfig['load_priority'] = 99;
            }

            $moduleConfig['is_package'] = 0;
            if (strpos($modulePath, base_path('vendor')) !== false) {
                $moduleConfig['is_package'] = 1;
            }

            // Check if module is discovered
            if (Str::contains($modulePath, 'synapps'.DS.'modules')) {
                $mainComposer = file_get_contents(base_path('composer.json'));
                if (strpos($mainComposer, addcslashes('synapps'.DS.'modules'.DS.$moduleConfig['id'], '\\')) === false) {
                    continue;
                }
            }

            $this->synapps->config['modules'][$moduleConfig['id']] = $moduleConfig;
        }

        $moduleSorted = collect($this->synapps->config['modules'])->sortBy('load_priority');
        $this->synapps->config['modules']
            = $this->synapps->config['modules_ori']
            = $moduleSorted->toArray();
    }

    private function loadSynAppsModuleConfig()
    {
        if (! file_exists(synapps_path('config/modules.json'))) {
            file_put_contents(synapps_path('config/modules.json'), '{}');
        }

        if (! file_exists(synapps_path('config/modules.dev.json'))) {
            file_put_contents(synapps_path('config/modules.dev.json'), '{}');
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
                return ! in_array($k, $protectedKeys);
            }, ARRAY_FILTER_USE_BOTH);
            $configs[$id] = $filteredConfig;
        }

        return $configs;
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
            json_encode($this->synapps->config['modules'], JSON_PRETTY_PRINT)
        );
    }
}
