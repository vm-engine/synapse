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

    /** @var list<string>|null Root composer.json PSR-4 paths, read once per build. */
    private ?array $autoloadPaths = null;

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

            // A directory module counts only once the root composer.json autoloads it (synapps:discover).
            if (Str::contains($modulePath, 'synapps'.DS.'modules') && ! $this->isAutoloaded((string) $moduleConfig['id'])) {
                continue;
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

    /**
     * Whether a root composer.json PSR-4 path points into synapps/modules/{id}/. Separators are
     * normalized, so a composer.json written on Windows ("synapps\modules\home\src\") counts on
     * Linux and vice versa, and the id must match a whole path segment (home ≠ homepage).
     */
    private function isAutoloaded(string $moduleId): bool
    {
        $this->autoloadPaths ??= $this->rootAutoloadPaths();
        $prefix = 'synapps/modules/'.$moduleId.'/';

        foreach ($this->autoloadPaths as $path) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Root composer.json PSR-4 paths, '/'-separated and '/'-terminated.
     *
     * @return list<string>
     */
    private function rootAutoloadPaths(): array
    {
        $composer = json_decode((string) @file_get_contents(base_path('composer.json')), true);
        $psr4 = is_array($composer) ? ($composer['autoload']['psr-4'] ?? []) : [];
        $paths = [];

        foreach (is_array($psr4) ? $psr4 : [] as $dirs) {
            foreach ((array) $dirs as $dir) {
                if (is_string($dir)) {
                    $paths[] = rtrim(ltrim(str_replace('\\', '/', $dir), './'), '/').'/';
                }
            }
        }

        return $paths;
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
