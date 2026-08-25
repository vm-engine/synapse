<?php

declare(strict_types=1);

use VmEngine\Synapse\Models\Language;
use VmEngine\Synapse\Services\DbLang;

test('triple underscore helper returns translation', function () {
    Language::create([
        'language_code' => 'en',
        'key' => 'welcome',
        'value' => 'Welcome to our application!',
    ]);

    // Need to clear the service instance to reload with new data
    app()->forgetInstance('dblang');
    app()->singleton('dblang', fn () => new DbLang);

    expect(___('welcome'))->toBe('Welcome to our application!');
});

test('triple underscore helper returns key when translation not found', function () {
    expect(___('missing.translation.key'))->toBe('missing.translation.key');
});

test('triple underscore helper works with dotted keys', function () {
    Language::create(['language_code' => 'en', 'key' => 'menu.home', 'value' => 'Home']);
    Language::create(['language_code' => 'en', 'key' => 'menu.about', 'value' => 'About']);

    // Clear and reload
    app()->forgetInstance('dblang');
    app()->singleton('dblang', fn () => new DbLang);

    expect(___('menu.home'))->toBe('Home')
        ->and(___('menu.about'))->toBe('About');
});

test('triple underscore helper handles unicode characters', function () {
    Language::create([
        'language_code' => 'en',
        'key' => 'greeting.arabic',
        'value' => 'مرحبا',
    ]);

    // Clear and reload
    app()->forgetInstance('dblang');
    app()->singleton('dblang', fn () => new DbLang);

    expect(___('greeting.arabic'))->toBe('مرحبا');
});

test('triple underscore helper loads from cache', function () {
    Language::create(['language_code' => 'en', 'key' => 'cached', 'value' => 'Cached Value']);

    // Initialize the service
    app()->forgetInstance('dblang');
    app()->singleton('dblang', fn () => new DbLang);

    // First call
    $first = ___('cached');

    // Update database (cache should not be affected)
    Language::where('language_code', 'en')->where('key', 'cached')->update(['value' => 'Modified Value']);

    // Should still return cached value
    $second = ___('cached');

    expect($first)->toBe('Cached Value')
        ->and($second)->toBe('Cached Value');
});

test('triple underscore helper handles empty translations', function () {
    Language::create(['language_code' => 'en', 'key' => 'empty', 'value' => '']);

    // Clear and reload
    app()->forgetInstance('dblang');
    app()->singleton('dblang', fn () => new DbLang);

    expect(___('empty'))->toBe('');
});

test('triple underscore helper handles special characters', function () {
    Language::create([
        'language_code' => 'en',
        'key' => 'special',
        'value' => 'Text with "quotes" & \'apostrophes\' <html>',
    ]);

    // Clear and reload
    app()->forgetInstance('dblang');
    app()->singleton('dblang', fn () => new DbLang);

    expect(___('special'))->toBe('Text with "quotes" & \'apostrophes\' <html>');
});

test('triple underscore helper can fetch from different language', function () {
    Language::create(['language_code' => 'en', 'key' => 'hello', 'value' => 'Hello']);
    Language::create(['language_code' => 'id', 'key' => 'hello', 'value' => 'Halo']);
    Language::create(['language_code' => 'es', 'key' => 'hello', 'value' => 'Hola']);

    // Clear and reload with default 'en'
    app()->forgetInstance('dblang');
    app()->singleton('dblang', fn () => new DbLang);

    expect(___('hello'))->toBe('Hello')
        ->and(___('hello', 'id'))->toBe('Halo')
        ->and(___('hello', 'es'))->toBe('Hola');
});

test('triple underscore helper supports multiple languages simultaneously', function () {
    Language::create(['language_code' => 'en', 'key' => 'greeting', 'value' => 'Good morning']);
    Language::create(['language_code' => 'id', 'key' => 'greeting', 'value' => 'Selamat pagi']);
    Language::create(['language_code' => 'fr', 'key' => 'greeting', 'value' => 'Bonjour']);

    // Clear and reload
    app()->forgetInstance('dblang');
    app()->singleton('dblang', fn () => new DbLang('en'));

    expect(___('greeting'))->toBe('Good morning')
        ->and(___('greeting', 'id'))->toBe('Selamat pagi')
        ->and(___('greeting', 'fr'))->toBe('Bonjour');
});
