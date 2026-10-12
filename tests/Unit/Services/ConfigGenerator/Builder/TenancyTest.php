<?php

declare(strict_types=1);

use VmEngine\Synapse\Services\ConfigGenerator\Builder;
use VmEngine\Synapse\Services\ConfigGenerator\Builder\Tenancy;

it('merges replicable models a module declares under tenancy in its module.json', function () {
    $builder = new Builder;
    $builder->config['tenancy'] = ['replicable_models' => ['App\\Models\\Plan']];
    $tenancy = new Tenancy($builder);

    $tenancy->loop('pos', ['id' => 'pos', 'tenancy' => ['replicable_models' => ['Pos\\Models\\Tax']]]);
    $tenancy->loop('crm', ['id' => 'crm', 'tenancy' => ['replicable_models' => ['Crm\\Models\\Stage']]]);

    expect($builder->config['tenancy']['replicable_models'])
        ->toBe(['App\\Models\\Plan', 'Pos\\Models\\Tax', 'Crm\\Models\\Stage']);
});

it('ignores modules that declare no replicable models', function () {
    $builder = new Builder;
    $builder->config['tenancy'] = ['replicable_models' => ['App\\Models\\Plan']];
    $tenancy = new Tenancy($builder);

    $tenancy->loop('home', ['id' => 'home']);
    $tenancy->loop('auth', ['id' => 'auth', 'tenancy' => ['seeders' => ['Some\\Seeder']]]);

    expect($builder->config['tenancy']['replicable_models'])->toBe(['App\\Models\\Plan']);
});

it('does nothing while tenancy has not been set up', function () {
    $builder = new Builder;
    $tenancy = new Tenancy($builder);

    $tenancy->loop('pos', ['id' => 'pos', 'tenancy' => ['replicable_models' => ['Pos\\Models\\Tax']]]);

    expect($builder->config)->not->toHaveKey('tenancy');
});

it('keeps the tenancy config in the compiled synapps config', function () {
    $builder = new Builder;
    $builder->config['tenancy'] = ['db_mode' => 3];

    (new Tenancy($builder))->finalize();

    // config('synapps.tenancy.*') is read from the compiled synapps.json.
    expect($builder->config['tenancy'] ?? null)->toBe(['db_mode' => 3]);
});

it('excludes the backend prefix as a bare path segment', function () {
    $builder = new Builder;
    $builder->config['auth'] = ['routes' => ['backend_prefix' => '/apps']];

    (new Tenancy($builder))->init();

    // SubfolderResolver compares against the first path segment, never '/apps'.
    expect($builder->config['tenancy']['excluded_prefixes'])
        ->toContain('apps')
        ->not->toContain('/apps');
})->skip(fn () => ! file_exists(synapps_path('config/tenancy.json')), 'needs a host app with tenancy:setup run');
