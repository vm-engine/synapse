<?php

declare(strict_types=1);

use VmEngine\Synapse\Console\Commands\SynAppsReconfig;

describe('config preservation on reconfig', function () {
    it('preserves existing themes config via config() default in SynAppsReconfig source', function () {
        $source = file_get_contents((new ReflectionClass(SynAppsReconfig::class))->getFileName());

        expect($source)->toContain("'themes' => config('synapps.apps.themes',");
    });

    it('preserves existing dependencies config via config() default in SynAppsReconfig source', function () {
        $source = file_get_contents((new ReflectionClass(SynAppsReconfig::class))->getFileName());

        expect($source)->toContain("'dependencies' => RteSetup::applyDependencies(config('synapps.apps.dependencies',");
    });

    it('preserves existing security config via config() default in SynAppsReconfig source', function () {
        $source = file_get_contents((new ReflectionClass(SynAppsReconfig::class))->getFileName());

        expect($source)->toContain("'security' => array_merge(config('synapps.apps.security',");
    });

    it('pre-fills the git_repo prompt with the existing config value', function () {
        $source = file_get_contents((new ReflectionClass(SynAppsReconfig::class))->getFileName());

        expect($source)->toContain("default: config('synapps.apps.git_repo', '')");
    });

    it('prompts for notifications settings, defaulting from current config', function () {
        $source = file_get_contents((new ReflectionClass(SynAppsReconfig::class))->getFileName());

        expect($source)->toContain("default: config('synapps.apps.notifications.enabled', false)")
            ->and($source)->toContain("'notifications' => [");
    });
});
