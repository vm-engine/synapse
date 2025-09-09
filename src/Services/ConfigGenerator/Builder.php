<?php

namespace VmEngine\Synapse\Services\ConfigGenerator;

use VmEngine\Synapse\Services\ConfigGenerator\Builder\Client;
use VmEngine\Synapse\Services\ConfigGenerator\Builder\System;

class Builder
{
    protected $configBuilder = [
        'apps' => Client::class,
        'system' => System::class,
    ];

    protected $builderInstances = [];
    protected $initComplete = false;

    public $synappsPath;
    public $mainConfig;

    public $config = []; //config AppConfig
    public $configHash = '';

    public function __construct()
    {
        $this->init();
        $this->configHash = hash('sha256', now()->format('Y-m-d H:i:s.u'));
    }

    private function init()
    {
        if (!$this->initComplete) {
            $this->buildConfigs();
            $this->initComplete = true;
        }
    }

    private function buildConfigs()
    {
        $this->synappsPath = synapps_path();
        $this->mainConfig = json_decode(
            file_get_contents(__DIR__ . '/../../../config.json'),
            true
        );
    }
}
