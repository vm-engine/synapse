<?php

declare(strict_types=1);

use VmEngine\Synapse\Console\Commands\SynAppsReconfig;

it('registers the --dev option', function () {
    expect((new SynAppsReconfig)->getDefinition()->hasOption('dev'))->toBeTrue();
});

it('writes changed values to apps.dev.json when --dev is given', function () {
    $source = (string) file_get_contents((new ReflectionClass(SynAppsReconfig::class))->getFileName());

    expect($source)->toContain("if (\$this->option('dev')) {")
        ->and($source)->toContain("synapps_path('config/apps.dev.json')")
        ->and($source)->toContain("config('synapps.apps', [])");
});
