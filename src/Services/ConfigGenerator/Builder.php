<?php

namespace VmEngine\Synapse\Services\ConfigGenerator;

use VmEngine\Synapse\Services\ConfigGenerator\Builder\Apps;
use VmEngine\Synapse\Services\ConfigGenerator\Builder\Binding;
use VmEngine\Synapse\Services\ConfigGenerator\Builder\Modules;
use VmEngine\Synapse\Services\ConfigGenerator\Builder\Navigation;
use VmEngine\Synapse\Services\ConfigGenerator\Builder\System;

class Builder
{
    protected $configBuilder = [
        'apps' => Apps::class,
        'system' => System::class,
        'modules' => Modules::class,
        'binding' => Binding::class,
        'navigation' => Navigation::class,
    ];

    protected $builderInstances = [];

    protected $initComplete = false;

    public $synappsPath;

    public $mainConfig;

    public $config = []; // config AppConfig

    public $configHash = '';

    public function __construct()
    {
        $this->init();
        $this->configHash = hash('sha256', now()->format('Y-m-d H:i:s.u'));
    }

    private function init()
    {
        if (! $this->initComplete) {
            $this->buildConfigs();
            $this->initComplete = true;
        }
    }

    private function buildConfigs()
    {
        $this->synappsPath = synapps_path();
        $this->mainConfig = json_decode(
            file_get_contents(__DIR__.'/../../../config.json'),
            true
        );

        $this->config = [
            'apps' > [],
            'system' => [],
            'binding' => [],
            'modules' => [],
            'modules_ori' => [], // config2 default dari module dan lib (tidak akan masuk ke config AppConfig)
            'nav' => [
                'frontend' => [],
                'backend' => [],
            ],
        ];
    }

    private function initLoop()
    {
        foreach ($this->configBuilder as $key => $value) {
            $this->builderInstances[$key] = new $value($this);
            $this->builderInstances[$key]->init();
        }
    }

    private function handlePreLoop()
    {
        foreach ($this->builderInstances as $instance) {
            $instance->preLoop();
        }
    }

    private function loopModules()
    {
        foreach ($this->builderInstances as $instance) {
            foreach ($this->config['modules'] as $moduleName => $moduleConfig) {
                $instance->loop($moduleName, $moduleConfig);
            }
        }
    }

    private function handlePostLoop()
    {
        foreach ($this->builderInstances as $instance) {
            $instance->postLoop();
        }
    }

    private function finalize()
    {
        foreach ($this->builderInstances as $instance) {
            $instance->finalize();
        }
    }

    public function generate()
    {
        $this->initLoop();
        $this->handlePreLoop();
        $this->loopModules();
        $this->handlePostLoop();
        $this->finalize();

        return $this->config;
    }
}
