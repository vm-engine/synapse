<?php

declare(strict_types=1);

use VmEngine\Synapse\Console\Commands\SynAppsReconfig;
use VmEngine\Synapse\Console\Commands\SynAppsSetup;

it('asks about the rich-text editor in setup and wires it in', function () {
    $source = commandSource(SynAppsSetup::class);

    expect($source)->toContain("label: 'Enable the rich-text editor?'")
        ->and($source)->toContain("'rte' => ['enabled' => \$rteEnabled],")
        ->and($source)->toContain('RteSetup::applyDependencies(')
        ->and($source)->toContain('RteSetup::syncAssets($rteEnabled);');
});

it('asks about the rich-text editor in reconfig, defaulting to the current setting', function () {
    $source = commandSource(SynAppsReconfig::class);

    expect($source)->toContain("label: 'Enable the rich-text editor?'")
        ->and($source)->toContain("default: (bool) config('synapps.apps.rte.enabled', false)")
        ->and($source)->toContain("'rte' => ['enabled' => \$rteEnabled],")
        ->and($source)->toContain("RteSetup::applyDependencies(config('synapps.apps.dependencies',")
        ->and($source)->toContain('RteSetup::syncAssets($rteEnabled);')
        ->and($source)->toContain("\$this->call('synapps:discover');");
});
