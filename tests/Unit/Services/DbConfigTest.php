<?php

use VmEngine\Synapse\Models\Config;
use VmEngine\Synapse\Services\DbConfig;

beforeEach(function () {
    $this->dbConfig = new DbConfig;
});

test('can set and get config value', function () {
    $value = $this->dbConfig->set('app', 'name', 'My Application');

    expect($value)->toBe('My Application')
        ->and($this->dbConfig->get('app', 'name'))->toBe('My Application');
});

test('get returns default value when config does not exist', function () {
    $value = $this->dbConfig->get('app', 'nonexistent', 'default value');

    expect($value)->toBe('default value');
});

test('get returns null when config does not exist and no default provided', function () {
    $value = $this->dbConfig->get('app', 'nonexistent');

    expect($value)->toBeNull();
});

test('get uses cache for subsequent calls', function () {
    Config::create([
        'group' => 'app',
        'key' => 'cached',
        'value' => 'original',
    ]);

    // First call - should hit database
    $firstCall = $this->dbConfig->get('app', 'cached');
    expect($firstCall)->toBe('original');

    // Update database directly
    Config::where('group', 'app')->where('key', 'cached')->update(['value' => 'modified']);

    // Second call - should use cache and return original
    $secondCall = $this->dbConfig->get('app', 'cached');
    expect($secondCall)->toBe('original');
});

test('get with force flag bypasses cache', function () {
    Config::create([
        'group' => 'app',
        'key' => 'forced',
        'value' => 'original',
    ]);

    // First call
    $this->dbConfig->get('app', 'forced');

    // Update database
    Config::where('group', 'app')->where('key', 'forced')->update(['value' => 'modified']);

    // Force get - should bypass cache
    $forcedValue = $this->dbConfig->get('app', 'forced', null, true);
    expect($forcedValue)->toBe('modified');
});

test('set updates cache', function () {
    $this->dbConfig->set('app', 'test', 'value1');
    $this->dbConfig->set('app', 'test', 'value2');

    expect($this->dbConfig->get('app', 'test'))->toBe('value2');
});

test('set creates new config in database', function () {
    $this->dbConfig->set('settings', 'theme', 'dark');

    $config = Config::where('group', 'settings')->where('key', 'theme')->first();

    expect($config)->not->toBeNull()
        ->and($config->value)->toBe('dark')
        ->and($config->group)->toBe('settings')
        ->and($config->key)->toBe('theme');
});

test('set updates existing config in database', function () {
    Config::create([
        'group' => 'settings',
        'key' => 'language',
        'value' => 'en',
    ]);

    $this->dbConfig->set('settings', 'language', 'id');

    $config = Config::where('group', 'settings')->where('key', 'language')->first();

    expect($config->value)->toBe('id')
        ->and(Config::where('group', 'settings')->where('key', 'language')->count())->toBe(1);
});

test('handles multiple groups independently', function () {
    $this->dbConfig->set('app', 'name', 'App Name');
    $this->dbConfig->set('mail', 'name', 'Mail Name');
    $this->dbConfig->set('cache', 'name', 'Cache Name');

    expect($this->dbConfig->get('app', 'name'))->toBe('App Name')
        ->and($this->dbConfig->get('mail', 'name'))->toBe('Mail Name')
        ->and($this->dbConfig->get('cache', 'name'))->toBe('Cache Name');
});

test('handles special characters in values', function () {
    $specialValue = "Line 1\nLine 2\tTabbed\r\n\"Quoted\" 'Single' <html>";

    $this->dbConfig->set('test', 'special', $specialValue);

    expect($this->dbConfig->get('test', 'special'))->toBe($specialValue);
});

test('handles empty string values', function () {
    $this->dbConfig->set('test', 'empty', '');

    expect($this->dbConfig->get('test', 'empty'))->toBe('');
});

test('handles null values', function () {
    $this->dbConfig->set('test', 'null', null);

    expect($this->dbConfig->get('test', 'null'))->toBeNull();
});

test('set can save with label', function () {
    $this->dbConfig->set('app', 'name', 'My App', 'Application Name');

    $config = Config::where('group', 'app')->where('key', 'name')->first();

    expect($config->value)->toBe('My App')
        ->and($config->label)->toBe('Application Name');
});

test('set without label does not overwrite existing label', function () {
    Config::create([
        'group' => 'app',
        'key' => 'setting',
        'value' => 'old',
        'label' => 'Original Label',
    ]);

    $this->dbConfig->set('app', 'setting', 'new');

    $config = Config::where('group', 'app')->where('key', 'setting')->first();

    expect($config->value)->toBe('new')
        ->and($config->label)->toBe('Original Label');
});

test('set with null label explicitly does not update label', function () {
    Config::create([
        'group' => 'app',
        'key' => 'labeled',
        'value' => 'value',
        'label' => 'Existing Label',
    ]);

    $this->dbConfig->set('app', 'labeled', 'new value', null);

    $config = Config::where('group', 'app')->where('key', 'labeled')->first();

    expect($config->value)->toBe('new value')
        ->and($config->label)->toBe('Existing Label');
});
