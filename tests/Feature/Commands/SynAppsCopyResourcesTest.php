<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

/**
 * synapps:copy-resources refreshes the host's copies of the stub resources.
 *
 * It must never delete host files the stubs do not ship: the per-layout entry
 * files (synapps-app.css, synapps-base.js, ...) live beside the stub copies and
 * are what Vite actually builds from.
 */
beforeEach(function () {
    File::makeDirectory(base_path('synapps/resources/css'), 0755, true, true);
    File::makeDirectory(base_path('synapps/resources/js'), 0755, true, true);
});

afterEach(function () {
    File::deleteDirectory(base_path('synapps'));
});

it('keeps host-only files that the stubs do not ship', function () {
    File::put(base_path('synapps/resources/css/synapps-app.css'), '@import "./theme-app.css";');
    File::put(base_path('synapps/resources/js/synapps-base.js'), "import './synapps.js';");

    $this->artisan('synapps:copy-resources', ['--css' => true, '--js' => true, '--force' => true])
        ->assertSuccessful();

    expect(base_path('synapps/resources/css/synapps-app.css'))->toBeFile()
        ->and(File::get(base_path('synapps/resources/css/synapps-app.css')))->toBe('@import "./theme-app.css";')
        ->and(base_path('synapps/resources/js/synapps-base.js'))->toBeFile();
});

it('still refreshes the files the stubs do ship', function () {
    File::put(base_path('synapps/resources/css/synapse-components.css'), '/* stale local copy */');

    $this->artisan('synapps:copy-resources', ['--css' => true, '--force' => true])
        ->assertSuccessful();

    expect(File::get(base_path('synapps/resources/css/synapse-components.css')))
        ->not->toBe('/* stale local copy */')
        ->toContain('.form-box');
});

it('replaces a destination file that is a hard link to its stub instead of writing through it', function () {
    $stub = realpath(__DIR__.'/../../../stubs/synapps/resources/css/synapse-components.css');
    $dest = base_path('synapps/resources/css/synapse-components.css');

    expect(@link($stub, $dest))->toBeTrue();
    $before = File::get($stub);

    $this->artisan('synapps:copy-resources', ['--css' => true, '--force' => true])
        ->assertSuccessful();

    // The stub must be untouched, and the destination must no longer share its inode.
    expect(File::get($stub))->toBe($before)
        ->and(fileinode($dest))->not->toBe(fileinode($stub));
});
