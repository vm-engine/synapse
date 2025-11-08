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

test('patch-tests: patches Pest.php with module paths', function () {
    $pestPath = base_path('tests/Pest.php');
    $originalContent = File::get($pestPath);

    // Create backup
    File::put($pestPath.'.backup', $originalContent);

    $this->artisan('synapps:patch-tests')
        ->assertSuccessful();

    $content = File::get($pestPath);
    expect($content)->toContain('synapps/modules/*/tests/Feature')
        ->and($content)->toContain('Browser');

    // Restore original
    File::put($pestPath, $originalContent);
    File::delete($pestPath.'.backup');
});

test('patch-tests: patches phpunit.xml with module testsuites', function () {
    $phpunitPath = base_path('phpunit.xml');
    $originalContent = File::get($phpunitPath);

    // Create backup
    File::put($phpunitPath.'.backup', $originalContent);

    $this->artisan('synapps:patch-tests')
        ->assertSuccessful();

    $content = File::get($phpunitPath);
    expect($content)->toContain('testsuite name="Browser"')
        ->and($content)->toContain('testsuite name="Modules"')
        ->and($content)->toContain('testsuite name="Packages"');

    // Restore original
    File::put($phpunitPath, $originalContent);
    File::delete($phpunitPath.'.backup');
});

test('patch-tests: adds environment variables to phpunit.xml', function () {
    $phpunitPath = base_path('phpunit.xml');
    $originalContent = File::get($phpunitPath);

    // Create backup
    File::put($phpunitPath.'.backup', $originalContent);

    $this->artisan('synapps:patch-tests')
        ->assertSuccessful();

    $content = File::get($phpunitPath);
    expect($content)->toContain('name="APP_ENV" value="testing"')
        ->and($content)->toContain('name="DB_DATABASE" value=":memory:"')
        ->and($content)->toContain('name="CACHE_STORE" value="array"');

    // Restore original
    File::put($phpunitPath, $originalContent);
    File::delete($phpunitPath.'.backup');
});
