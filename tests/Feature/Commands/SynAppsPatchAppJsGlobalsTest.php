<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

/**
 * synapps:patch js adds the Synapse global helpers (withBack + the $withBack magic) to app.js.
 * Runs in a private base path: other test files write resources/js in the shared Testbench root.
 */
beforeEach(function () {
    $this->originalBasePath = base_path();
    $this->sandbox = sys_get_temp_dir().'/synapse-app-js-globals-'.bin2hex(random_bytes(6));
    File::ensureDirectoryExists($this->sandbox.'/resources/js');
    app()->setBasePath($this->sandbox);
    File::put(resource_path('js/app.js'), "import './bootstrap';\n");
});

afterEach(function () {
    app()->setBasePath($this->originalBasePath);
    File::deleteDirectory($this->sandbox);
});

it('registers the $withBack magic even when Alpine is already running', function () {
    $this->artisan('synapps:patch', ['object' => ['js']]);

    $appJs = File::get(resource_path('js/app.js'));

    // alpine:init fires once per full page load, not after a wire:navigate.
    expect($appJs)->toContain('// Synapse global helpers')
        ->toContain("Alpine.magic('withBack'")
        ->toContain('if (window.Alpine)')
        ->not->toMatch("/addEventListener\\('alpine:init', \\(\\) =>/");
});
