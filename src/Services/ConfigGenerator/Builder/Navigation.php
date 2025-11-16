<?php

namespace VmEngine\Synapse\Services\ConfigGenerator\Builder;

use VmEngine\Synapse\Services\ConfigGenerator\BuilderBase;

class Navigation extends BuilderBase
{
    public function loop($namespace, $config)
    {
        $backendNav = $config['nav']['backend'] ?? [];
        if (count($backendNav)) {
            if (! isset($backendNav['position'])) {
                $backendNav['position'] = count($this->synapps->config['nav']['backend']);
            }
            $this->synapps->config['nav']['backend'][$namespace] = $backendNav;
        }
        unset($this->synapps->config['modules'][$namespace]['nav']['backend']);
    }

    public function postLoop()
    {
        // Sort by postition
        $backend = collect($this->synapps->config['nav']['backend'])
            ->sortBy('position');
        $this->synapps->config['nav']['backend'] = $backend->toArray();
    }

    public function finalize()
    {
        file_put_contents(
            synapps_path('config/compiled/nav.backend.json'),
            json_encode($this->synapps->config['nav']['backend'], JSON_PRETTY_PRINT)
        );
    }
}
