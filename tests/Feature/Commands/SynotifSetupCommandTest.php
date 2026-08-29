<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use VmEngine\SynAuth\Models\Role;

beforeEach(function () {
    $this->appsJsonPath = base_path('synapps/config/apps.json');
    File::ensureDirectoryExists(dirname($this->appsJsonPath));

    $this->originalAppsJson = File::exists($this->appsJsonPath) ? File::get($this->appsJsonPath) : null;

    File::put($this->appsJsonPath, json_encode([
        'app_code' => 'test_app',
        'app_name' => 'Test App',
        'themes' => ['active' => 'default'],
    ], JSON_PRETTY_PRINT));
});

afterEach(function () {
    if ($this->originalAppsJson !== null) {
        File::put($this->appsJsonPath, $this->originalAppsJson);
    } else {
        File::delete($this->appsJsonPath);
    }

    Schema::dropIfExists('notifications');
});

it('aborts when synapps-auth is not installed', function () {
    $this->artisan('synotif:setup')
        ->expectsOutputToContain('vm-engine/synapps-auth')
        ->assertExitCode(1);

    $config = json_decode(File::get($this->appsJsonPath), true);
    expect($config)->not->toHaveKey('notifications');
})->skip(fn () => class_exists(Role::class), 'synapps-auth is installed in this workspace');

it('writes the notifications block into apps.json while preserving other keys', function () {
    if (! class_exists(Role::class)) {
        $this->markTestSkipped('synapps-auth is not installed in this workspace');
    }

    $this->artisan('synotif:setup')
        ->expectsConfirmation('Enable in-app notifications?', 'yes')
        ->expectsQuestion('Log channel for skipped/disabled notifications (blank = silent)', '')
        ->assertExitCode(0);

    $config = json_decode(File::get($this->appsJsonPath), true);

    expect($config['notifications'])->toBe(['enabled' => true, 'log_channel' => null])
        ->and($config['app_code'])->toBe('test_app')
        ->and(Schema::hasTable('notifications'))->toBeTrue();
});
