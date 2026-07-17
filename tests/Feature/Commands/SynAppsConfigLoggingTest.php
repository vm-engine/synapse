<?php

declare(strict_types=1);

use VmEngine\Synapse\Console\Commands\SynAppsReconfig;
use VmEngine\Synapse\Console\Commands\SynAppsSetup;

describe('logging config generation', function () {
    it('includes a logging block with defaults in SynAppsSetup source', function () {
        $source = file_get_contents((new ReflectionClass(SynAppsSetup::class))->getFileName());

        expect($source)->toContain("'logging' => [");
        expect($source)->toContain("'sensitive_keys' =>");
        expect($source)->toContain("'max_body_size' => 65536");
    });

    it('preserves existing logging config via config() default in SynAppsReconfig source', function () {
        $source = file_get_contents((new ReflectionClass(SynAppsReconfig::class))->getFileName());

        expect($source)->toContain("'logging' => config('synapps.apps.logging',");
    });
});
