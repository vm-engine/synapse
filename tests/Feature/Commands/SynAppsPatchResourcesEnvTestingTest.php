<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

/**
 * Tests for SynAppsPatchResources tests patch (ensureEnvTesting) APP_KEY generation.
 *
 * Files are created in the Testbench Laravel root (base_path()) and cleaned up after each test.
 */
afterEach(function () {
    File::delete(base_path('.env.testing'));
    File::delete(base_path('.env.testing.bak'));
});

it('generates an APP_KEY when creating .env.testing', function () {
    File::delete(base_path('.env.testing'));

    $this->artisan('synapps:patch', ['object' => ['tests']]);

    expect(File::get(base_path('.env.testing')))->toMatch('/^APP_KEY=base64:[A-Za-z0-9+\/]{43}=$/m');
});

it('fills an empty APP_KEY in an existing .env.testing', function () {
    File::put(base_path('.env.testing'), "APP_ENV=testing\nAPP_KEY=\n");

    $this->artisan('synapps:patch', ['object' => ['tests']]);

    expect(File::get(base_path('.env.testing')))
        ->toMatch('/^APP_KEY=base64:\S+$/m')
        ->not->toMatch('/^APP_KEY=$/m');
});

it('keeps an existing APP_KEY untouched', function () {
    File::put(base_path('.env.testing'), "APP_ENV=testing\nAPP_KEY=base64:existing\n");

    $this->artisan('synapps:patch', ['object' => ['tests']]);

    expect(File::get(base_path('.env.testing')))->toContain('APP_KEY=base64:existing');
});
