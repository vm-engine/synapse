<?php

use VmEngine\Synapse\Facades\DbConfig;
use VmEngine\Synapse\Models\Config;

test('facade can set and get config', function () {
    DbConfig::set('app', 'name', 'Test App');

    expect(DbConfig::get('app', 'name'))->toBe('Test App');
});

test('facade get returns default when not found', function () {
    expect(DbConfig::get('nonexistent', 'key', 'default'))->toBe('default');
});

test('facade can set with label', function () {
    DbConfig::set('app', 'version', '1.0.0', 'App Version');

    $config = Config::where('group', 'app')->where('key', 'version')->first();

    expect($config->label)->toBe('App Version');
});

test('facade get can force reload from database', function () {
    Config::create([
        'group' => 'test',
        'key' => 'forced',
        'value' => 'original',
    ]);

    // First call - caches the value
    DbConfig::get('test', 'forced');

    // Update database
    Config::where('group', 'test')->where('key', 'forced')->update(['value' => 'modified']);

    // Force reload
    expect(DbConfig::get('test', 'forced', null, true))->toBe('modified');
});

test('facade resolves to correct service instance', function () {
    $instance = DbConfig::getFacadeRoot();

    expect($instance)->toBeInstanceOf(VmEngine\Synapse\Services\DbConfig::class);
});
