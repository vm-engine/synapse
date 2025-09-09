<?php

namespace VmEngine\Synapse\Services\ConfigGenerator;

class BuilderBase
{
    protected $synapps;

    public function __construct(&$synapps)
    {
        $this->synapps = $synapps;
    }

    public function init() {}

    public function preLoop() {}

    public function loop($packageNamespace, $packageConfig) {}

    public function postLoop() {}

    public function finalize() {}
}
