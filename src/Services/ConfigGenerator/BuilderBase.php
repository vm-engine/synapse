<?php

namespace VmEngine\Synapse\Services\ConfigGenerator;

class BuilderBase
{
    protected Builder $synapps;

    public function __construct(Builder &$synapps)
    {
        $this->synapps = $synapps;
    }

    public function init() {}

    public function preLoop() {}

    public function loop(string $packageNamespace, array $packageConfig) {}

    public function postLoop() {}

    public function finalize() {}
}
