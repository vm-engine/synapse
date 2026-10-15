<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use VmEngine\Synapse\Services\SynapseConfigGenerator;

it('orders module seed paths by load_priority, defaulting to 99', function (): void {
    $root = sys_get_temp_dir().'/synapse-seed-order-'.uniqid();
    $modules = ['alpha' => 50, 'beta' => null, 'zeta' => 0];
    foreach ($modules as $name => $priority) {
        File::makeDirectory("$root/$name/database/seeders", 0755, true);
        file_put_contents("$root/$name/module.json", json_encode(array_filter(['id' => $name, 'load_priority' => $priority], fn ($value) => $value !== null)));
    }

    $config = ['namespaces' => ['general' => ['Test\Modules' => [$root, false]]]];

    try {
        (function () use (&$config): void {
            self::setDatabasePaths($config);
        })->bindTo(null, SynapseConfigGenerator::class)();
    } finally {
        File::deleteDirectory($root);
    }

    expect($config['seed_path']['general'])->toBe([
        "$root/zeta/database/seeders/seeders.php",
        "$root/alpha/database/seeders/seeders.php",
        "$root/beta/database/seeders/seeders.php",
    ]);
});
