<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use VmEngine\Synapse\Rte\RteSetup;

beforeEach(function () {
    File::ensureDirectoryExists(resource_path('js'));
    File::ensureDirectoryExists(base_path('synapps/resources/js'));
});

afterEach(function () {
    File::delete([resource_path('js/app.js'), base_path('synapps/resources/js/synapse-rte.js')]);
});

it('is off unless the config flag is on', function () {
    expect(RteSetup::enabled())->toBeFalse();

    config(['synapps.apps.rte.enabled' => true]);

    expect(RteSetup::enabled())->toBeTrue();
});

it('adds its packages to a dependency block when enabled and removes them when disabled', function () {
    $block = ['npm' => ['main' => ['jquery' => '^3.7'], 'dev' => []], 'composer' => ['main' => [], 'dev' => []]];

    $on = RteSetup::applyDependencies($block, true);

    expect($on['npm']['main'])->toBe(['jquery' => '^3.7', 'jodit' => '^4.15'])
        ->and($on['composer']['main'])->toBe(['mews/purifier' => '^3.4'])
        ->and(RteSetup::applyDependencies($on, true))->toBe($on);

    $pinned = RteSetup::applyDependencies(['npm' => ['main' => ['jodit' => '^4.20']]], true);
    expect($pinned['npm']['main']['jodit'])->toBe('^4.20');

    $off = RteSetup::applyDependencies($on, false);

    expect($off['npm']['main'])->toBe(['jquery' => '^3.7'])
        ->and($off['composer']['main'])->toBeInstanceOf(stdClass::class);

    // Nothing to remove: the block comes back untouched, so `synapps:reconfig --dev` records no change.
    expect(RteSetup::applyDependencies($block, false))->toBe($block);
});

it('copies the script and adds the import after the synapps import when enabled', function () {
    file_put_contents(resource_path('js/app.js'), "import '../../synapps/resources/js/synapps-app.js';\nimport './custom.js';\n");

    expect(RteSetup::syncAssets(true))->toBeTrue()
        ->and(file_exists(base_path('synapps/resources/js/synapse-rte.js')))->toBeTrue()
        ->and(file_get_contents(resource_path('js/app.js')))->toBe(
            "import '../../synapps/resources/js/synapps-app.js';\n".RteSetup::APP_JS_IMPORT."\nimport './custom.js';\n"
        )
        ->and(RteSetup::syncAssets(true))->toBeFalse();
});

it('appends the import when app.js has no synapps import, and removes it when disabled', function () {
    file_put_contents(resource_path('js/app.js'), "import './custom.js';\n");

    RteSetup::syncAssets(true);
    expect(file_get_contents(resource_path('js/app.js')))->toBe("import './custom.js';\n".RteSetup::APP_JS_IMPORT."\n");

    expect(RteSetup::syncAssets(false))->toBeTrue()
        ->and(file_get_contents(resource_path('js/app.js')))->toBe("import './custom.js';\n")
        ->and(RteSetup::syncAssets(false))->toBeFalse();
});

it('does nothing to a host without app.js beyond copying the script', function () {
    expect(RteSetup::syncAssets(true))->toBeTrue()
        ->and(file_exists(resource_path('js/app.js')))->toBeFalse();
});
