<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Services\ConfigGenerator;

class BuilderBase
{
    protected Builder $synapps;

    public function __construct(Builder &$synapps)
    {
        $this->synapps = $synapps;
    }

    public function init(): void {}

    public function preLoop(): void {}

    /** @param  array<string, mixed>  $packageConfig */
    public function loop(string $packageNamespace, array $packageConfig): void {}

    public function postLoop(): void {}

    public function finalize(): void {}
}
