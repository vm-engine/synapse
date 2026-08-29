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

test('synapps:make class dispatches to synapps:make-class and generates a plain class', function () {
    $this->artisan('synapps:make', [
        'object' => 'class',
        'module' => 'demo',
        'name' => 'Helper',
    ])->assertSuccessful();

    expect($this->modulePath.'/src/Helper.php')->toBeFile();
});

test('synapps:make rejects an unknown component type', function () {
    $this->artisan('synapps:make', [
        'object' => 'not-a-real-type',
        'module' => 'demo',
        'name' => 'Helper',
    ])->assertFailed();
});

test('synapps:make requires a name unless the object is module', function () {
    $this->artisan('synapps:make', [
        'object' => 'class',
        'module' => 'demo',
    ])->assertFailed();

    expect($this->modulePath.'/src')->toBeDirectory()
        ->and(glob($this->modulePath.'/src/*.php'))->toBe([]);
});

test('synapps:make does not require a name when the object is module', function () {
    $this->artisan('synapps:make', [
        'object' => 'module',
        'module' => 'widgets',
        '--no-interaction' => true,
    ])->expectsOutputToContain('Running: synapps:make-module');

    (new Filesystem)->deleteDirectory(base_path('synapps/modules/widgets'));
});
