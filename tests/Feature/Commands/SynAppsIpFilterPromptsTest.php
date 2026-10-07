<?php

declare(strict_types=1);

use VmEngine\Synapse\Console\Commands\SynAppsReconfig;
use VmEngine\Synapse\Console\Commands\SynAppsSetup;

function commandSource(string $class): string
{
    return (string) file_get_contents((new ReflectionClass($class))->getFileName());
}

it('setup prompts for ip lists and writes them under security', function () {
    $source = commandSource(SynAppsSetup::class);

    expect($source)->toContain("label: 'Whitelisted IPs'")
        ->and($source)->toContain("label: 'Blacklisted IPs'")
        ->and($source)->toContain("'ip_whitelist' => \$ipWhitelist")
        ->and($source)->toContain("'ip_blacklist' => \$ipBlacklist");
});

it('reconfig pre-fills ip lists from config and keeps other security keys', function () {
    $source = commandSource(SynAppsReconfig::class);

    expect($source)->toContain("config('synapps.apps.security.ip_whitelist', [])")
        ->and($source)->toContain("config('synapps.apps.security.ip_blacklist', [])")
        ->and($source)->toContain("'ip_whitelist' => \$ipWhitelist")
        ->and($source)->toContain("config('synapps.apps.security',");
});
