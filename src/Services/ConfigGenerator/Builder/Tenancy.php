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

        $this->loadModuleTenancyConfig();
    }

    public function finalize(): void
    {
        if (! $this->hasTenancy()) {
            return;
        }
        file_put_contents(
            synapps_path('config/compiled/tenancy.json'),
            json_encode($this->synapps->config['tenancy'], JSON_PRETTY_PRINT)
        );
        unset($this->synapps->config['tenancy']);
    }

    private function loadTenancyConfig(): void
    {
        $tenancyJson = json_decode(
            file_get_contents(synapps_path('config/tenancy.json')),
            true
        );

        if (! file_exists(synapps_path('config/tenancy.dev.json'))) {
            file_put_contents(synapps_path('config/tenancy.dev.json'), '{}');
        }

        $tmpEnvTenancy = json_decode(
            file_get_contents(synapps_path('config/tenancy.dev.json')),
            true
        );

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
                $this->synapps->config['auth']['routes']['backend_prefix'] ?? 'backend'
            );
        }
    }

    private function loadModuleTenancyConfig(): void
    {
        $modules = array_merge(
            glob(synapps_path('modules/*/tenancy.json')),
            glob(base_path('vendor/*/synmod-*/tenancy.json')),
            glob(base_path('vendor/*/synmodule-*/tenancy.json')),
            glob(base_path('vendor/*/synapps-*/tenancy.json')),
        );

        foreach ($modules as $modulePath) {
            $moduleTenancyConfig = read_json_file($modulePath);

            if (isset($moduleTenancyConfig['replicable_models'])) {
                $this->synapps->config['tenancy']['replicable_models'] = array_merge(
                    $this->synapps->config['tenancy']['replicable_models'] ?? [],
                    $moduleTenancyConfig['replicable_models']
                );
            }
        }
    }

    private function hasTenancy(): bool
    {
        return file_exists(synapps_path('config/tenancy.json'));
    }
}
