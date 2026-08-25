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
