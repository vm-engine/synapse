<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

/**
 * Tests for SynAppsPatchResources tests patch: package test paths (vendor/*\/synmod-*, synmodule-*, synapps-*)
 * are only added to tests/Pest.php and phpunit.xml when matching package folders exist.
 *
 * Files are created in the Testbench Laravel root (base_path()) and cleaned up after each test.
 */
beforeEach(function () {
    File::ensureDirectoryExists(base_path('tests'));
    File::put(base_path('tests/Pest.php'), <<<'PHP'
        <?php

        pest()->extend(Tests\TestCase::class)
            ->in('Feature');
        PHP);
    File::put(base_path('phpunit.xml'), <<<'XML'
        <?xml version="1.0" encoding="UTF-8"?>
        <phpunit>
          <testsuites>
            <testsuite name="Feature">
              <directory>tests/Feature</directory>
            </testsuite>
          </testsuites>
          <php/>
        </phpunit>
        XML);
});

afterEach(function () {
    File::delete([
        base_path('tests/Pest.php'),
        base_path('tests/Pest.php.bak'),
        base_path('phpunit.xml'),
        base_path('phpunit.xml.bak'),
        base_path('.env.testing'),
        base_path('.env.testing.bak'),
    ]);
    File::deleteDirectory(base_path('vendor/synapse-test-vendor'));
});

it('skips package test paths when no package folders exist', function () {
    $this->artisan('synapps:patch', ['object' => ['tests']]);

    expect(File::get(base_path('tests/Pest.php')))
        ->toContain("'../synapps/modules/*/tests/Feature'")
        ->not->toContain('synmod-')
        ->not->toContain('synmodule-')
        ->not->toContain('synapps-*');

    expect(File::get(base_path('phpunit.xml')))
        ->toContain('<testsuite name="Modules">')
        ->not->toContain('<testsuite name="Packages">');
});

it('adds only the package test paths whose folders exist', function () {
    File::ensureDirectoryExists(base_path('vendor/synapse-test-vendor/synmod-demo/tests/Feature'));
    File::ensureDirectoryExists(base_path('vendor/synapse-test-vendor/synmod-demo/tests/Unit'));

    $this->artisan('synapps:patch', ['object' => ['tests']]);

    expect(File::get(base_path('tests/Pest.php')))
        ->toContain("'../vendor/*/synmod-*/tests/Feature'")
        ->toContain("'../vendor/*/synmod-*/tests/Unit'")
        ->not->toContain('synmodule-')
        ->not->toContain('synapps-*');

    expect(File::get(base_path('phpunit.xml')))
        ->toContain('<testsuite name="Packages">')
        ->toContain('<directory>vendor/*/synmod-*/tests</directory>')
        ->not->toContain('synmodule-')
        ->not->toContain('synapps-*');
});
