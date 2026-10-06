<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Services\ConfigGenerator\Builder;

use VmEngine\Synapse\Services\ConfigGenerator\BuilderBase;

class Navigation extends BuilderBase
{
    /** @param  array<string, mixed>  $config */
    public function loop(string $namespace, array $config): void
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

    public function postLoop(): void
    {
        // Sort by postition
        /** @var array<string, mixed> $backendNav */
        $backendNav = $this->synapps->config['nav']['backend'];
        $backend = collect($backendNav)->sortBy('position');
        $this->synapps->config['nav']['backend'] = $backend->toArray();
    }

    public function finalize(): void
    {
        file_put_contents(
            synapps_path('config/compiled/nav.backend.json'),
            json_encode($this->synapps->config['nav']['backend'], JSON_PRETTY_PRINT)
        );
    }
}
