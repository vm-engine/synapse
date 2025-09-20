<?php

namespace VmEngine\Synapse\Services\ConfigGenerator\Builder;

use VmEngine\Synapse\Services\ConfigGenerator\BuilderBase;

class Apps extends BuilderBase
{
    public function init()
    {
        $synappsPath = $this->synapps->synappsPath;
        $this->synapps->config['apps'] = json_decode(
            file_get_contents($synappsPath . '/config/apps.json'),
            true
        );
        if (!file_exists($synappsPath . '/config/apps.dev.json')) {
            file_put_contents(
                $synappsPath . '/config/apps.dev.json',
                json_encode($this->synapps->config['apps'], JSON_PRETTY_PRINT)
            );
        }

        $tmpEnvClient = json_decode(
            file_get_contents($synappsPath . '/config/apps.dev.json'),
            true
        );
        $this->synapps->config['apps'] = array_replace_recursive(
            $this->synapps->config['apps'],
            $tmpEnvClient
        );
    }

    public function finalize()
    {
        file_put_contents(
            $this->synapps->synappsPath . '/config/compiled/apps.json',
            json_encode($this->synapps->config['apps'], JSON_PRETTY_PRINT)
        );
    }
}
