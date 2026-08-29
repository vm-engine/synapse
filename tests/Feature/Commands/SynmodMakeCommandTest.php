<?php

declare(strict_types=1);
use Illuminate\Filesystem\Filesystem;

beforeEach(function () {
    $this->modulePath = base_path('synapps/modules/demo');
    mkdir($this->modulePath.'/src', 0755, true);
});

afterEach(function () {
    (new Filesystem)->deleteDirectory(base_path('synapps/modules/demo'));
});

test('synmod-make class dispatches to synapps:make-class and generates a plain class', function () {
    $this->artisan('synmod-make', [
        'type' => 'class',
        'module' => 'demo',
        'name' => 'Helper',
    ])->assertSuccessful();

    $path = $this->modulePath.'/src/Helper.php';

    expect($path)->toBeFile();

    $content = file_get_contents($path);

    expect($content)->not->toContain('{{')
        ->and($content)->toContain('namespace')
        ->and($content)->toContain('Demo;')
        ->and($content)->toContain('class Helper');
});

test('synmod-make rejects an unknown component type', function () {
    $this->artisan('synmod-make', [
        'type' => 'not-a-real-type',
        'module' => 'demo',
        'name' => 'Helper',
    ])->assertFailed();
});

test('synmod-make recognizes the module type (previously missing from the map)', function () {
    // Full end-to-end module generation isn't exercised here: it depends on
    // ModuleGeneratorService's package-vs-directory path heuristic, which is
    // unrelated to this fix and misfires under Testbench's vendor-nested test
    // skeleton. What this pins down is the actual bug being fixed: 'module'
    // used to fail before ever reaching synapps:make-module.
    $this->artisan('synmod-make', [
        'type' => 'module',
        'module' => 'widgets',
        '--no-interaction' => true,
    ])->expectsOutputToContain('Running: synapps:make-module');

    (new Filesystem)->deleteDirectory(base_path('synapps/modules/widgets'));
});
