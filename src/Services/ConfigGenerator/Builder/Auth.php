<?php

namespace VmEngine\Synapse\Services\ConfigGenerator\Builder;

use VmEngine\Synapse\Services\ConfigGenerator\BuilderBase;

class Auth extends BuilderBase
{
    public function init()
    {
        if (! $this->hasAuth()) {
            return;
        }
        $authJson = json_decode(
            file_get_contents(synapps_path('config/auth.json')),
            true
        );
        $this->synapps->config['auth'] = $authJson;
        $this->synapps->config['acl'] = [];
    }

    public function loop($packageNamespace, $packageConfig)
    {
        if (! $this->hasAuth()) {
            return;
        }
        if (! isset($packageConfig['acl'])) {
            $packageConfig['acl'] = [
                'index' => [
                    'label' => '__'.$packageNamespace.'::acl.index',
                    'create' => 0,
                    'read' => 1,
                    'update' => 0,
                    'delete' => 0,
                ],
            ];
        }
        $this->synapps->config['acl'][$packageNamespace] = $packageConfig['acl'];
    }

    public function finalize()
    {
        if (! $this->hasAuth()) {
            return;
        }
        file_put_contents(
            synapps_path('config/compiled/acl.json'),
            json_encode($this->synapps->config['acl'], JSON_PRETTY_PRINT)
        );
        unset($this->synapps->config['acl']);
    }

    private function hasAuth()
    {
        return file_exists(synapps_path('config/auth.json'));
    }
}
