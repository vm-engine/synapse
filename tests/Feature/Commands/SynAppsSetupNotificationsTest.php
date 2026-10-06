<?php

declare(strict_types=1);

use VmEngine\Synapse\Console\Commands\SynAppsSetup;

it('prompts for notifications and adds the block to apps.json when synapps-auth is installed', function () {
    $source = file_get_contents((new ReflectionClass(SynAppsSetup::class))->getFileName());

    expect($source)->toContain('NotificationSetup::authInstalled()')
        ->and($source)->toContain("'notifications' => [")
        ->and($source)->toContain('Enable in-app notifications?');
});
