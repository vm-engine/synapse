<?php

declare(strict_types=1);

use VmEngine\Synapse\Models\Config;

test('dbconf helper can get config value', function () {
    Config::create([
        'group' => 'app',
        'key' => 'name',
        'value' => 'My Application',
    ]);

    expect(dbconf('app.name'))->toBe('My Application');
});

test('dbconf helper returns default when not found', function () {
    expect(dbconf('nonexistent.key', 'default value'))->toBe('default value');
});

test('dbconf helper can set config with array', function () {
    $result = dbconf(['app.version' => '1.0.0']);

    expect($result)->toBe('1.0.0')
        ->and(Config::where('group', 'app')->where('key', 'version')->first()->value)->toBe('1.0.0');
});

test('dbconf helper with exclamation mark forces database read', function () {
    Config::create([
        'group' => 'test',
        'key' => 'forced',
        'value' => 'original',
    ]);

    // First call caches
    dbconf('test.forced');

    // Update database
    Config::where('group', 'test')->where('key', 'forced')->update(['value' => 'modified']);

    // Force read with !
    expect(dbconf('!test.forced'))->toBe('modified');
});

test('dbconf helper uses cache by default', function () {
    Config::create([
        'group' => 'cache',
        'key' => 'test',
        'value' => 'original',
    ]);

    // First call
    dbconf('cache.test');

    // Update database
    Config::where('group', 'cache')->where('key', 'test')->update(['value' => 'modified']);

    // Should return cached value
    expect(dbconf('cache.test'))->toBe('original');
});

test('dbconf helper with force parameter bypasses cache', function () {
    Config::create([
        'group' => 'force',
        'key' => 'test',
        'value' => 'original',
    ]);

    // Cache it
    dbconf('force.test');

    // Update
    Config::where('group', 'force')->where('key', 'test')->update(['value' => 'modified']);

    // Force bypass cache
    expect(dbconf('force.test', null, true))->toBe('modified');
});

test('dbconf helper handles dotted keys in group', function () {
    $result = dbconf(['mail.smtp.host' => 'smtp.example.com']);

    expect($result)->toBe('smtp.example.com')
        ->and(Config::where('group', 'mail')->where('key', 'smtp.host')->first()->value)
        ->toBe('smtp.example.com');
});

test('dbconf helper returns null when config not found and no default', function () {
    expect(dbconf('missing.config'))->toBeNull();
});

test('dbconf helper handles empty string values', function () {
    dbconf(['app.empty' => '']);

    expect(dbconf('app.empty'))->toBe('');
});

test('dbconf helper handles special characters', function () {
    dbconf(['app.special' => 'Value with "quotes" & \'apostrophes\'']);

    expect(dbconf('app.special'))->toBe('Value with "quotes" & \'apostrophes\'');
});
