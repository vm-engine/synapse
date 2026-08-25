<?php

declare(strict_types=1);

use VmEngine\Synapse\Facades\DbLang;
use VmEngine\Synapse\Models\Language;

test('facade can set and get language', function () {
    DbLang::set('welcome', 'Welcome!');

    expect(DbLang::get('welcome'))->toBe('Welcome!');
});

test('facade get returns key when translation not found', function () {
    expect(DbLang::get('missing.translation'))->toBe('missing.translation');
});

test('facade can set with label', function () {
    DbLang::set('greeting', 'Hello', 'Greeting Message');

    $language = Language::where('language_code', 'en')->where('key', 'greeting')->first();

    expect($language->label)->toBe('Greeting Message');
});

test('facade loads existing translations on instantiation', function () {
    Language::create(['language_code' => 'en', 'key' => 'preloaded', 'value' => 'Preloaded Value']);

    // Clear any existing instance
    DbLang::clearResolvedInstance('dblang');

    expect(DbLang::get('preloaded'))->toBe('Preloaded Value');
});

test('facade resolves to correct service instance', function () {
    $instance = DbLang::getFacadeRoot();

    expect($instance)->toBeInstanceOf(VmEngine\Synapse\Services\DbLang::class);
});

test('facade can get language code', function () {
    expect(DbLang::getLanguage())->toBe('en');
});

test('facade can set language code', function () {
    Language::create(['language_code' => 'en', 'key' => 'test', 'value' => 'English']);
    Language::create(['language_code' => 'id', 'key' => 'test', 'value' => 'Indonesian']);

    DbLang::clearResolvedInstance('dblang');

    expect(DbLang::get('test'))->toBe('English');

    DbLang::setLanguage('id');

    expect(DbLang::get('test'))->toBe('Indonesian');
});

test('facade can fetch from different language without changing current', function () {
    Language::create(['language_code' => 'en', 'key' => 'hello', 'value' => 'Hello']);
    Language::create(['language_code' => 'es', 'key' => 'hello', 'value' => 'Hola']);

    DbLang::clearResolvedInstance('dblang');

    expect(DbLang::get('hello'))->toBe('Hello')
        ->and(DbLang::get('hello', 'es'))->toBe('Hola')
        ->and(DbLang::getLanguage())->toBe('en');
});

test('facade can set translation for specific language', function () {
    DbLang::clearResolvedInstance('dblang');

    DbLang::set('greeting', 'Halo', null, 'id');

    $language = Language::where('language_code', 'id')->where('key', 'greeting')->first();

    expect($language)->not->toBeNull()
        ->and($language->value)->toBe('Halo');
});
