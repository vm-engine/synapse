<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Services\ConfigGenerator;

use Illuminate\Support\Str;
use VmEngine\Synapse\Services\ConfigGenerator\Builder\Apps;
use VmEngine\Synapse\Services\ConfigGenerator\Builder\Auth;
use VmEngine\Synapse\Services\ConfigGenerator\Builder\Binding;
use VmEngine\Synapse\Services\ConfigGenerator\Builder\Modules;
use VmEngine\Synapse\Services\ConfigGenerator\Builder\Navigation;
use VmEngine\Synapse\Services\ConfigGenerator\Builder\Tenancy;

class Builder
{
    /** @var array<string, class-string<BuilderBase>> */
    protected array $configBuilder = [
        'apps' => Apps::class,
        'modules' => Modules::class,
        'binding' => Binding::class,
        'navigation' => Navigation::class,
        'auth' => Auth::class,
        'tenancy' => Tenancy::class,
    ];

    /** @var array<string, BuilderBase> */
    protected array $builderInstances = [];

    protected bool $initComplete = false;

    public string $synappsPath;

    /** @var array<string, mixed> */
    public array $mainConfig;

    /** @var array<string, mixed> */
    public array $config = []; // config AppConfig

    public string $configHash = '';

    public function __construct()
    {
        $this->init();
        $this->configHash = hash('sha256', now()->format('Y-m-d H:i:s.u'));
    }

    private function init(): void
    {
        if (! $this->initComplete) {
            $this->buildConfigs();
            $this->initComplete = true;
        }
    }

    private function buildConfigs(): void
    {
        $this->synappsPath = synapps_path();
        $this->mainConfig = read_json_file(__DIR__.'/../../../config.json');

        $this->config = [
            'apps' => [],
            'binding' => [],
            'modules' => [],
            'modules_ori' => [], // config2 default dari module dan lib (tidak akan masuk ke config AppConfig)
            'nav' => [
                'frontend' => [],
                'backend' => [],
            ],
        ];
    }

    private function initLoop(): void
    {
        foreach ($this->configBuilder as $key => $value) {
            $this->builderInstances[$key] = new $value($this);
            $this->builderInstances[$key]->init();
        }
    }

    private function handlePreLoop(): void
    {
        foreach ($this->builderInstances as $instance) {
            $instance->preLoop();
        }
    }

    private function loopModules(): void
    {
        foreach ($this->builderInstances as $instance) {
            foreach ($this->config['modules'] as $moduleName => $moduleConfig) {
                $instance->loop($moduleName, $moduleConfig);
            }
        }
    }

    private function handlePostLoop(): void
    {
        foreach ($this->builderInstances as $instance) {
            $instance->postLoop();
        }
    }

    private function finalize(): void
    {
        foreach ($this->builderInstances as $instance) {
            $instance->finalize();
        }
    }

    private function fetchEnvValues(): void
    {
        array_walk_recursive($this->config, function (&$value) {
            if (is_string($value) && Str::startsWith($value, 'env::')) {
                $value = env(Str::after($value, 'env::'));
            }
        });
    }

    private function writeFinalFile(): void
    {
        $finalPath = synapps_path('config/compiled/synapps.json');
        if (! file_exists(dirname($finalPath))) {
            mkdir(dirname($finalPath), 0775, true);
        }
        file_put_contents($finalPath, json_encode($this->config, JSON_PRETTY_PRINT));
    }

    /** @return array<string, mixed> */
    public function generate(): array
    {
        $this->initLoop();
        $this->handlePreLoop();
        $this->loopModules();
        $this->handlePostLoop();
        $this->finalize();

        $this->fetchEnvValues();

        $this->writeFinalFile();

        return $this->config;
    }
}
