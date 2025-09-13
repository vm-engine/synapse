<?php

namespace VmEngine\Synapse\Services\ConfigGenerator\Builder;

use VmEngine\Synapse\Services\ConfigGenerator\BuilderBase;

class Binding extends BuilderBase
{
    protected $providers = [];

    public function init()
    {
        $synapseConfig = include config_path('synapse.php');
        $this->synapps->config['binding'] = $synapseConfig['bindings'];
    }

    public function loop($namespace, $config)
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

    public function postLoop()
    {
        $this->mergeBinding('interface');
        $this->mergeBinding('class');
        $this->mergeBinding('alias');
        $this->mergeBinding('route');

        if (! empty($this->providers)) {
            $baseSystem = $this->synapps->config['system']['providers'] ?? [];
            $this->synapps->config['system']['providers'] = array_merge(
                $baseSystem,
                $this->providers
            );
        }
    }

    private function mergeBinding($key)
    {
        $systemConfig = $this->synapps->config['system']['binding'][$key] ?? null;
        if ($systemConfig) {
            foreach ($systemConfig as $contract => $service) {
                $this->synapps->config['binding'][$key][$contract] = $service;
            }
        }

    }

    public function finalize()
    {
        file_put_contents(
            synapps_path('config/compiled/binding.json'),
            json_encode($this->synapps->config['binding'], JSON_PRETTY_PRINT)
        );
    }
}
