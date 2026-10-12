<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Services\ConfigGenerator\Builder;

use VmEngine\Synapse\Services\ConfigDiff;
use VmEngine\Synapse\Services\ConfigGenerator\BuilderBase;

class Auth extends BuilderBase
{
    public function init(): void
    {
        if (! $this->hasAuth()) {
            return;
        }
        $authJson = read_json_file(synapps_path('config/auth.json'));

        $this->synapps->config['auth'] = $authJson;
        $this->synapps->config['acl'] = [];

        if (! file_exists(synapps_path('config/auth.dev.json'))) {
            file_put_contents(synapps_path('config/auth.dev.json'), '{}');
        }

        $tmpEnvAuth = read_json_file(synapps_path('config/auth.dev.json'));
        $this->synapps->config['auth'] = ConfigDiff::merge(
            $this->synapps->config['auth'],
            $tmpEnvAuth
        );
    }

    /** @param  array<string, mixed>  $packageConfig */
    public function loop(string $packageNamespace, array $packageConfig): void
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

    public function finalize(): void
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

    private function hasAuth(): bool
    {
        return file_exists(synapps_path('config/auth.json'));
    }
}
