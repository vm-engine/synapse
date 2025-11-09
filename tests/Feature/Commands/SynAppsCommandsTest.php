<?php

use Illuminate\Support\Facades\File;

test('patch-resources: patches app.js with synapps import', function () {
    // Create test resources directory
    $jsPath = resource_path('js/app.js');
    $cssPath = resource_path('css/app.css');
    File::ensureDirectoryExists(dirname($jsPath));
    File::ensureDirectoryExists(dirname($cssPath));
    File::put($jsPath, "import './bootstrap';\n");
    File::put($cssPath, "@import 'tailwindcss';\n");

    $this->artisan('synapps:patch-resources')
        ->assertSuccessful();

    $content = File::get($jsPath);
    expect($content)->toContain('synapps/resources/js/synapps.js');

    // Cleanup
    File::delete($jsPath);
    File::delete($cssPath);
});

test('patch-resources: patches app.css with synapps import', function () {
    // Create test resources directory
    $jsPath = resource_path('js/app.js');
    $cssPath = resource_path('css/app.css');
    File::ensureDirectoryExists(dirname($jsPath));
    File::ensureDirectoryExists(dirname($cssPath));
    File::put($jsPath, "import './bootstrap';\n");
    File::put($cssPath, "@import 'tailwindcss';\n");

    $this->artisan('synapps:patch-resources')
        ->assertSuccessful();

    $content = File::get($cssPath);
    expect($content)->toContain('synapps/resources/css/synapps.css');

    // Cleanup
    File::delete($jsPath);
    File::delete($cssPath);
});

test('patch-resources: is idempotent', function () {
    // Create test resources
    $jsPath = resource_path('js/app.js');
    $cssPath = resource_path('css/app.css');
    File::ensureDirectoryExists(dirname($jsPath));
    File::ensureDirectoryExists(dirname($cssPath));
    File::put($jsPath, "import './bootstrap';\n");
    File::put($cssPath, "@import 'tailwindcss';\n");

    // Run twice
    $this->artisan('synapps:patch-resources')->assertSuccessful();
    $firstJs = File::get($jsPath);
    $firstCss = File::get($cssPath);

    $this->artisan('synapps:patch-resources')->assertSuccessful();
    $secondJs = File::get($jsPath);
    $secondCss = File::get($cssPath);

    expect($firstJs)->toBe($secondJs)
        ->and($firstCss)->toBe($secondCss);

    // Cleanup
    File::delete($jsPath);
    File::delete($cssPath);
});

test('patch-resources: returns success exit code', function () {
    $jsPath = resource_path('js/app.js');
    $cssPath = resource_path('css/app.css');
    File::ensureDirectoryExists(dirname($jsPath));
    File::ensureDirectoryExists(dirname($cssPath));
    File::put($jsPath, "import './bootstrap';\n");
    File::put($cssPath, "@import 'tailwindcss';\n");

    $this->artisan('synapps:patch-resources')
        ->assertExitCode(0);

    // Cleanup
    File::delete($jsPath);
    File::delete($cssPath);
});

/*
 * Note: The synapps:patch-tests command is designed to patch actual Laravel project files
 * (tests/Pest.php, phpunit.xml, composer.json, .env.testing) and should be tested manually
 * in a real Laravel application context rather than in isolated package tests.
 */
