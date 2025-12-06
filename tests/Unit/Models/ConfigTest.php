<?php

use VmEngine\Synapse\Models\Config;

test('can create config', function () {
    $config = Config::create([
        'group' => 'app',
        'key' => 'name',
        'value' => 'My Application',
        'label' => 'Application Name',
    ]);

    expect($config)->toBeInstanceOf(Config::class)
        ->and($config->group)->toBe('app')
        ->and($config->key)->toBe('name')
        ->and($config->value)->toBe('My Application')
        ->and($config->label)->toBe('Application Name');
});

test('group can be explicitly set', function () {
    $config = Config::create([
        'group' => 'custom',
        'key' => 'test',
        'value' => 'value',
    ]);

    expect($config->group)->toBe('custom');
});

test('unique constraint on group and key', function () {
    Config::create([
        'group' => 'app',
        'key' => 'name',
        'value' => 'First',
    ]);

    expect(fn () => Config::create([
        'group' => 'app',
        'key' => 'name',
        'value' => 'Second',
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});

test('allows same key in different groups', function () {
    Config::create([
        'group' => 'app',
        'key' => 'name',
        'value' => 'App Name',
    ]);

    Config::create([
        'group' => 'mail',
        'key' => 'name',
        'value' => 'Mail Name',
    ]);

    expect(Config::where('key', 'name')->count())->toBe(2);
});

test('can query by group and key', function () {
    Config::create(['group' => 'app', 'key' => 'name', 'value' => 'My App']);

    $config = Config::where('group', 'app')->where('key', 'name')->first();

    expect($config)->not->toBeNull()
        ->and($config->value)->toBe('My App');
});

test('value can be null', function () {
    $config = Config::create([
        'group' => 'test',
        'key' => 'nullable',
        'value' => null,
    ]);

    expect($config->value)->toBeNull();
});

test('label can be null', function () {
    $config = Config::create([
        'group' => 'test',
        'key' => 'no_label',
        'value' => 'value',
    ]);

    expect($config->label)->toBeNull();
});

test('updateOrCreate creates new config', function () {
    $config = Config::updateOrCreate(
        ['group' => 'new', 'key' => 'test'],
        ['value' => 'created']
    );

    expect($config->wasRecentlyCreated)->toBeTrue()
        ->and($config->value)->toBe('created');
});

test('updateOrCreate updates existing config', function () {
    Config::create([
        'group' => 'existing',
        'key' => 'test',
        'value' => 'old',
    ]);

    $config = Config::updateOrCreate(
        ['group' => 'existing', 'key' => 'test'],
        ['value' => 'new']
    );

    expect($config->wasRecentlyCreated)->toBeFalse()
        ->and($config->value)->toBe('new')
        ->and(Config::where('group', 'existing')->where('key', 'test')->count())->toBe(1);
});

test('has timestamps', function () {
    $config = Config::create([
        'group' => 'test',
        'key' => 'timestamp',
        'value' => 'value',
    ]);

    expect($config->created_at)->not->toBeNull()
        ->and($config->updated_at)->not->toBeNull();
});

test('can store long text values', function () {
    $longText = str_repeat('Lorem ipsum dolor sit amet. ', 1000);

    $config = Config::create([
        'group' => 'test',
        'key' => 'long',
        'value' => $longText,
    ]);

    expect($config->value)->toBe($longText);
});
