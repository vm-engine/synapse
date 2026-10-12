<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Services\ConfigGenerator\Builder;

use VmEngine\Synapse\Services\ConfigGenerator\BuilderBase;

class Tenancy extends BuilderBase
{
    public function init(): void
    {
        if (! $this->hasTenancy()) {
            return;
        }

        $this->loadTenancyConfig();
    }

    /**
     * Merge the replicable models a module declares under `tenancy` in its
     * own module.json into the installation-wide list.
     *
     * @param  array<string, mixed>  $packageConfig
     */
    public function loop(string $packageNamespace, array $packageConfig): void
    {
        // Only set once init() has loaded synapps/config/tenancy.json.
        if (! isset($this->synapps->config['tenancy'])) {
            return;
        }

        $this->synapps->config['tenancy']['replicable_models'] = array_merge(
            $this->synapps->config['tenancy']['replicable_models'] ?? [],
            $packageConfig['tenancy']['replicable_models'] ?? []
        );
    }

    private function loadTenancyConfig(): void
    {
        $tenancyJson = read_json_file(synapps_path('config/tenancy.json'));

        if (! file_exists(synapps_path('config/tenancy.dev.json'))) {
            file_put_contents(synapps_path('config/tenancy.dev.json'), '{}');
        }

        $tmpEnvTenancy = read_json_file(synapps_path('config/tenancy.dev.json'));

        $this->synapps->config['tenancy'] = array_replace_recursive(
            $tenancyJson,
            $tmpEnvTenancy
        );

        $this->validateTenancyConfig();
    }

    private function validateTenancyConfig(): void
    {
        $this->synapps->config['tenancy']['db_mode'] = ! in_array(
            $this->synapps->config['tenancy']['db_mode'],
            [1, 3]
        ) ? 3 : $this->synapps->config['tenancy']['db_mode'];

        $this->synapps->config['tenancy']['resolvers'] ??= [
            'custom_domain', 'subdomain', 'subfolder',
        ];

        $this->synapps->config['tenancy']['excluded_prefixes'] ??= [
            'api', 'horizon', '_debugbar', 'livewire', 'sanctum',
        ];

        if (isset($this->synapps->config['auth'])) {
            array_unshift(
                $this->synapps->config['tenancy']['excluded_prefixes'],
                trim($this->synapps->config['auth']['routes']['backend_prefix'] ?? 'backend', '/')
            );
        }
    }

    private function hasTenancy(): bool
    {
        return file_exists(synapps_path('config/tenancy.json'));
    }
}
