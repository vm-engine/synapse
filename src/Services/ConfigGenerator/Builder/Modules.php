<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Services\ConfigGenerator\Builder;

use Illuminate\Support\Str;
use VmEngine\Synapse\Services\ConfigGenerator\BuilderBase;

class Modules extends BuilderBase
{
    protected string $moduleString;

    /** @var array<string, mixed> */
    protected array $tmpModulesDev;

    /** @var array<string, mixed> */
    protected array $configAppOverride;

    /** @var array<string, mixed> */
    protected array $configDevOverride;

    public function init(): void
    {
        $this->loadModulePackageConfig();
        $this->loadSynAppsModuleConfig();
        $this->mergeConfigs();
    }

    private function loadModulePackageConfig(): void
    {
        $modules = array_merge(
            glob(synapps_path('modules/*/module.json')) ?: [],
            glob(base_path('vendor/*/synmod-*/module.json')) ?: [],
            glob(base_path('vendor/*/synmodule-*/module.json')) ?: [],
            glob(base_path('vendor/*/synapps-*/module.json')) ?: [],
        );

        foreach ($modules as $modulePath) {
            $moduleConfig = read_json_file($modulePath);

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
                if ($mainComposer === false || strpos($mainComposer, addcslashes('synapps'.DS.'modules'.DS.$moduleConfig['id'], '\\')) === false) {
                    continue;
                }
            }

            $this->synapps->config['modules'][$moduleConfig['id']] = $moduleConfig;
        }

        /** @var array<string, mixed> $modulesConfig */
        $modulesConfig = $this->synapps->config['modules'];
        $moduleSorted = collect($modulesConfig)->sortBy('load_priority');
        $this->synapps->config['modules']
            = $this->synapps->config['modules_ori']
            = $moduleSorted->toArray();
    }

    private function loadSynAppsModuleConfig(): void
    {
        if (! file_exists(synapps_path('config/modules.json'))) {
            file_put_contents(synapps_path('config/modules.json'), '{}');
        }

        if (! file_exists(synapps_path('config/modules.dev.json'))) {
            file_put_contents(synapps_path('config/modules.dev.json'), '{}');
        }

        $this->configAppOverride = $this->sanitizeConfigOverrides(
            read_json_file(synapps_path('config/modules.json'))
        );

        $this->configDevOverride = $this->sanitizeConfigOverrides(
            read_json_file(synapps_path('config/modules.dev.json'))
        );
    }

    /**
     * @param  array<string, mixed>  $configs
     * @return array<string, mixed>
     */
    private function sanitizeConfigOverrides(array $configs): array
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

    private function mergeConfigs(): void
    {
        $this->synapps->config['modules'] = array_replace_recursive(
            $this->synapps->config['modules'],
            $this->configAppOverride,
            $this->configDevOverride
        );
    }

    public function finalize(): void
    {
        file_put_contents(
            synapps_path('config/compiled/modules.json'),
            json_encode($this->synapps->config['modules'], JSON_PRETTY_PRINT)
        );
    }
}
