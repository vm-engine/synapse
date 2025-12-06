<?php

use VmEngine\Synapse\Models\Config;

test('command creates config with value', function () {
    $this->artisan('synapse:db-config', [
        'key' => 'app.name',
        'value' => 'My Application',
    ])
        ->assertSuccessful();

    $config = Config::where('group', 'app')->where('key', 'name')->first();

    expect($config)->not->toBeNull()
        ->and($config->value)->toBe('My Application');
});

test('command creates config with label', function () {
    $this->artisan('synapse:db-config', [
        'key' => 'app.name',
        'value' => 'My App',
        '--label' => 'Application Name',
    ])
        ->assertSuccessful();

    $config = Config::where('group', 'app')->where('key', 'name')->first();

    expect($config->value)->toBe('My App')
        ->and($config->label)->toBe('Application Name');
});

test('command creates config without value', function () {
    $this->artisan('synapse:db-config', [
        'key' => 'app.test',
    ])
        ->assertSuccessful();

    $config = Config::where('group', 'app')->where('key', 'test')->first();

    expect($config)->not->toBeNull()
        ->and($config->value)->toBeNull();
});

test('command updates existing config', function () {
    Config::create([
        'group' => 'app',
        'key' => 'existing',
        'value' => 'old value',
    ]);

    $this->artisan('synapse:db-config', [
        'key' => 'app.existing',
        'value' => 'new value',
    ])
        ->assertSuccessful();

    expect(Config::where('group', 'app')->where('key', 'existing')->count())->toBe(1)
        ->and(Config::where('group', 'app')->where('key', 'existing')->first()->value)->toBe('new value');
});

test('command handles dotted key format', function () {
    $this->artisan('synapse:db-config', [
        'key' => 'mail.smtp.host',
        'value' => 'smtp.example.com',
    ])
        ->assertSuccessful();

    $config = Config::where('group', 'mail')->where('key', 'smtp.host')->first();

    expect($config->value)->toBe('smtp.example.com');
});

test('command with label updates label', function () {
    Config::create([
        'group' => 'app',
        'key' => 'setting',
        'value' => 'value',
        'label' => 'Old Label',
    ]);

    $this->artisan('synapse:db-config', [
        'key' => 'app.setting',
        'value' => 'value',
        '--label' => 'New Label',
    ])
        ->assertSuccessful();

    expect(Config::where('group', 'app')->where('key', 'setting')->first()->label)->toBe('New Label');
});

test('command without label preserves existing label', function () {
    Config::create([
        'group' => 'app',
        'key' => 'preserve',
        'value' => 'old',
        'label' => 'Keep This Label',
    ]);

    $this->artisan('synapse:db-config', [
        'key' => 'app.preserve',
        'value' => 'new',
    ])
        ->assertSuccessful();

    expect(Config::where('group', 'app')->where('key', 'preserve')->first()->label)->toBe('Keep This Label');
});

test('command handles special characters in value', function () {
    $this->artisan('synapse:db-config', [
        'key' => 'app.special',
        'value' => 'Value with "quotes" and \'apostrophes\'',
    ])
        ->assertSuccessful();

    expect(Config::where('group', 'app')->where('key', 'special')->first()->value)
        ->toBe('Value with "quotes" and \'apostrophes\'');
});

test('command handles empty string value', function () {
    $this->artisan('synapse:db-config', [
        'key' => 'app.empty',
        'value' => '',
    ])
        ->assertSuccessful();

    expect(Config::where('group', 'app')->where('key', 'empty')->first()->value)->toBe('');
});
