<?php

use VmEngine\Synapse\Models\Language;
use VmEngine\Synapse\Services\DbLang;

beforeEach(function () {
    // Set multi-language config for these tests
    config(['synapps.apps.multi_language' => true]);
    config(['synapps.apps.enabled_languages' => ['en', 'id', 'es']]);
});

test('multi-language auto-creates entries for all enabled languages', function () {
    $dbLang = new DbLang('en');

    $dbLang->set('welcome', 'Welcome!');

    // Should create entries for all enabled languages
    expect(Language::where('key', 'welcome')->count())->toBe(3)
        ->and(Language::where('language_code', 'en')->where('key', 'welcome')->first()->value)->toBe('Welcome!')
        ->and(Language::where('language_code', 'id')->where('key', 'welcome')->exists())->toBeTrue()
        ->and(Language::where('language_code', 'es')->where('key', 'welcome')->exists())->toBeTrue();
});

test('multi-language creates entries with null value for other languages', function () {
    $dbLang = new DbLang('en');

    $dbLang->set('greeting', 'Hello');

    $indonesian = Language::where('language_code', 'id')->where('key', 'greeting')->first();
    $spanish = Language::where('language_code', 'es')->where('key', 'greeting')->first();

    expect($indonesian)->not->toBeNull()
        ->and($indonesian->value)->toBeNull()
        ->and($spanish)->not->toBeNull()
        ->and($spanish->value)->toBeNull();
});

test('multi-language copies label to all languages', function () {
    $dbLang = new DbLang('en');

    $dbLang->set('message', 'Test Message', 'Message Label');

    expect(Language::where('language_code', 'en')->where('key', 'message')->first()->label)->toBe('Message Label')
        ->and(Language::where('language_code', 'id')->where('key', 'message')->first()->label)->toBe('Message Label')
        ->and(Language::where('language_code', 'es')->where('key', 'message')->first()->label)->toBe('Message Label');
});

test('multi-language does not overwrite existing translations', function () {
    // Pre-create Indonesian translation
    Language::create([
        'language_code' => 'id',
        'key' => 'existing',
        'value' => 'Sudah Ada',
    ]);

    $dbLang = new DbLang('en');
    $dbLang->set('existing', 'Already Exists');

    // Indonesian should still have its original value
    expect(Language::where('language_code', 'id')->where('key', 'existing')->first()->value)->toBe('Sudah Ada')
        ->and(Language::where('language_code', 'en')->where('key', 'existing')->first()->value)->toBe('Already Exists')
        ->and(Language::where('key', 'existing')->count())->toBe(3); // en, id, es
});

test('multi-language does not auto-create when specific language is provided', function () {
    $dbLang = new DbLang('en');

    // Set only for Indonesian, should not auto-create for other languages
    $dbLang->set('specific', 'Spesifik', null, 'id');

    expect(Language::where('key', 'specific')->count())->toBe(1)
        ->and(Language::where('language_code', 'id')->where('key', 'specific')->exists())->toBeTrue()
        ->and(Language::where('language_code', 'en')->where('key', 'specific')->exists())->toBeFalse()
        ->and(Language::where('language_code', 'es')->where('key', 'specific')->exists())->toBeFalse();
});

test('multi-language works with different current languages', function () {
    // Create DbLang with Indonesian as current
    $dbLang = new DbLang('id');

    $dbLang->set('test', 'Tes');

    // Should create for all languages with 'id' having the value
    expect(Language::where('key', 'test')->count())->toBe(3)
        ->and(Language::where('language_code', 'id')->where('key', 'test')->first()->value)->toBe('Tes')
        ->and(Language::where('language_code', 'en')->where('key', 'test')->first()->value)->toBeNull()
        ->and(Language::where('language_code', 'es')->where('key', 'test')->first()->value)->toBeNull();
});

test('multi-language disabled does not auto-create entries', function () {
    config(['synapps.apps.multi_language' => false]);

    $dbLang = new DbLang('en');
    $dbLang->set('single', 'Single Language');

    // Should only create for current language
    expect(Language::where('key', 'single')->count())->toBe(1)
        ->and(Language::where('language_code', 'en')->where('key', 'single')->exists())->toBeTrue()
        ->and(Language::where('language_code', 'id')->where('key', 'single')->exists())->toBeFalse();
});

test('multi-language handles updating existing translation', function () {
    $dbLang = new DbLang('en');

    // First set
    $dbLang->set('update_test', 'Version 1');

    // Update
    $dbLang->set('update_test', 'Version 2');

    // Should still have 3 entries (one for each language)
    expect(Language::where('key', 'update_test')->count())->toBe(3)
        ->and(Language::where('language_code', 'en')->where('key', 'update_test')->first()->value)->toBe('Version 2');
});

test('multi-language works with only one enabled language', function () {
    config(['synapps.apps.enabled_languages' => ['en']]);

    $dbLang = new DbLang('en');
    $dbLang->set('solo', 'Solo Language');

    // Should only create for the one enabled language
    expect(Language::where('key', 'solo')->count())->toBe(1)
        ->and(Language::where('language_code', 'en')->where('key', 'solo')->first()->value)->toBe('Solo Language');
});

test('multi-language get returns key for untranslated entries', function () {
    $dbLang = new DbLang('en');

    // Set English translation (creates null for id and es)
    $dbLang->set('fallback_test', 'English Text');

    // Switch to Indonesian
    $dbLang->setLanguage('id');

    // Should return the key since value is null
    expect($dbLang->get('fallback_test'))->toBe('fallback_test');

    // Switch to English
    $dbLang->setLanguage('en');
    expect($dbLang->get('fallback_test'))->toBe('English Text');
});

test('multi-language supports many enabled languages', function () {
    config(['synapps.apps.enabled_languages' => ['en', 'id', 'es', 'fr', 'de', 'ja', 'zh']]);

    $dbLang = new DbLang('en');
    $dbLang->set('multilingual', 'Many Languages');

    expect(Language::where('key', 'multilingual')->count())->toBe(7);
});
