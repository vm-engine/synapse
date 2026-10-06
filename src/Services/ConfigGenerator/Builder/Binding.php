<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Services\ConfigGenerator\Builder;

use VmEngine\Synapse\Services\ConfigGenerator\BuilderBase;

class Binding extends BuilderBase
{
    /** @var array<int, mixed> */
    protected array $providers = [];

    public function init(): void
    {
        $synapseConfig = include config_path('synapse.php');
        $this->synapps->config['binding'] = $synapseConfig['bindings'];
    }

    /** @param  array<string, mixed>  $config */
    public function loop(string $namespace, array $config): void
    {
        if (isset($config['binding']) && isset($config['binding']['interface'])) {
            foreach ($config['binding']['interface'] as $contract => $service) {
                $this->synapps->config['binding']['interface'][$contract] = $service;
            }
        }

        if (isset($config['providers'])) {
            foreach ($config['providers'] as $provider) {
                $this->providers[] = $provider;
            }
        }
    }

    public function postLoop(): void
    {
        if (! empty($this->providers)) {
            $baseSystem = $this->synapps->config['system']['providers'] ?? [];
            $this->synapps->config['system']['providers'] = array_merge(
                $baseSystem,
                $this->providers
            );
        }
    }

    public function finalize(): void
    {
        file_put_contents(
            synapps_path('config/compiled/binding.json'),
            json_encode($this->synapps->config['binding'], JSON_PRETTY_PRINT)
        );
    }
}
