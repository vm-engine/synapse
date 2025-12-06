<?php

use VmEngine\Synapse\Models\Language;
use VmEngine\Synapse\Services\DbLang;

test('constructor loads languages for default language code', function () {
    Language::create(['language_code' => 'en', 'key' => 'welcome', 'value' => 'Welcome!']);
    Language::create(['language_code' => 'en', 'key' => 'goodbye', 'value' => 'Goodbye!']);
    Language::create(['language_code' => 'id', 'key' => 'welcome', 'value' => 'Selamat datang!']);

    $dbLang = new DbLang;

    expect($dbLang->get('welcome'))->toBe('Welcome!')
        ->and($dbLang->get('goodbye'))->toBe('Goodbye!');
});

test('constructor accepts custom language code', function () {
    Language::create(['language_code' => 'en', 'key' => 'hello', 'value' => 'Hello']);
    Language::create(['language_code' => 'id', 'key' => 'hello', 'value' => 'Halo']);

    $dbLang = new DbLang('id');

    expect($dbLang->get('hello'))->toBe('Halo');
});

test('setLanguage changes current language', function () {
    Language::create(['language_code' => 'en', 'key' => 'greeting', 'value' => 'Hello']);
    Language::create(['language_code' => 'id', 'key' => 'greeting', 'value' => 'Halo']);
    Language::create(['language_code' => 'es', 'key' => 'greeting', 'value' => 'Hola']);

    $dbLang = new DbLang('en');
    expect($dbLang->get('greeting'))->toBe('Hello');

    $dbLang->setLanguage('id');
    expect($dbLang->get('greeting'))->toBe('Halo');

    $dbLang->setLanguage('es');
    expect($dbLang->get('greeting'))->toBe('Hola');
});

test('getLanguage returns current language code', function () {
    $dbLang = new DbLang('id');

    expect($dbLang->getLanguage())->toBe('id');
});

test('get returns value when key exists', function () {
    Language::create(['language_code' => 'en', 'key' => 'hello', 'value' => 'Hello World']);

    $dbLang = new DbLang;

    expect($dbLang->get('hello'))->toBe('Hello World');
});

test('get returns key when translation does not exist', function () {
    $dbLang = new DbLang;

    expect($dbLang->get('nonexistent.key'))->toBe('nonexistent.key');
});

test('get can fetch from different language without changing current', function () {
    Language::create(['language_code' => 'en', 'key' => 'hello', 'value' => 'Hello']);
    Language::create(['language_code' => 'id', 'key' => 'hello', 'value' => 'Halo']);

    $dbLang = new DbLang('en');

    expect($dbLang->get('hello'))->toBe('Hello')
        ->and($dbLang->get('hello', 'id'))->toBe('Halo')
        ->and($dbLang->getLanguage())->toBe('en'); // Still 'en'
});

test('set creates new language entry for current language', function () {
    $dbLang = new DbLang;

    $value = $dbLang->set('greeting', 'Hello');

    expect($value)->toBe('Hello')
        ->and(Language::where('language_code', 'en')->where('key', 'greeting')->first()->value)->toBe('Hello');
});

test('set can create for specific language code', function () {
    $dbLang = new DbLang('en');

    $dbLang->set('hello', 'Halo', null, 'id');

    $language = Language::where('language_code', 'id')->where('key', 'hello')->first();

    expect($language)->not->toBeNull()
        ->and($language->value)->toBe('Halo');
});

test('set updates existing language entry', function () {
    Language::create(['language_code' => 'en', 'key' => 'message', 'value' => 'Old Message']);

    $dbLang = new DbLang;
    $value = $dbLang->set('message', 'New Message');

    expect($value)->toBe('New Message')
        ->and(Language::where('language_code', 'en')->where('key', 'message')->first()->value)->toBe('New Message')
        ->and(Language::where('language_code', 'en')->where('key', 'message')->count())->toBe(1);
});

test('set updates cache for current language only', function () {
    $dbLang = new DbLang('en');

    $dbLang->set('cached', 'Value 1');
    expect($dbLang->get('cached'))->toBe('Value 1');

    // Set for different language - should not update cache
    $dbLang->set('cached', 'Indonesian Value', null, 'id');
    expect($dbLang->get('cached'))->toBe('Value 1'); // Still English

    // Update current language - should update cache
    $dbLang->set('cached', 'Value 2');
    expect($dbLang->get('cached'))->toBe('Value 2');
});

test('handles special characters in translations', function () {
    $specialValue = "Line 1\nLine 2\tTabbed\r\n\"Quoted\" 'Single' <html>";

    $dbLang = new DbLang;
    $dbLang->set('special', $specialValue);

    expect($dbLang->get('special'))->toBe($specialValue);
});

test('handles empty string translations', function () {
    $dbLang = new DbLang;
    $dbLang->set('empty', '');

    expect($dbLang->get('empty'))->toBe('');
});

test('cache is populated from database on instantiation', function () {
    Language::create(['language_code' => 'en', 'key' => 'preloaded1', 'value' => 'Value 1']);
    Language::create(['language_code' => 'en', 'key' => 'preloaded2', 'value' => 'Value 2']);
    Language::create(['language_code' => 'en', 'key' => 'preloaded3', 'value' => 'Value 3']);

    $dbLang = new DbLang;

    // Should get all from cache without additional DB queries
    expect($dbLang->get('preloaded1'))->toBe('Value 1')
        ->and($dbLang->get('preloaded2'))->toBe('Value 2')
        ->and($dbLang->get('preloaded3'))->toBe('Value 3');
});

test('returns key for missing translation after cache loaded', function () {
    Language::create(['language_code' => 'en', 'key' => 'exists', 'value' => 'I exist']);

    $dbLang = new DbLang;

    expect($dbLang->get('exists'))->toBe('I exist')
        ->and($dbLang->get('does.not.exist'))->toBe('does.not.exist');
});

test('handles dotted key notation', function () {
    $dbLang = new DbLang;

    $dbLang->set('menu.home', 'Home');
    $dbLang->set('menu.about', 'About');
    $dbLang->set('menu.contact', 'Contact');

    expect($dbLang->get('menu.home'))->toBe('Home')
        ->and($dbLang->get('menu.about'))->toBe('About')
        ->and($dbLang->get('menu.contact'))->toBe('Contact');
});

test('handles unicode characters', function () {
    $dbLang = new DbLang;

    $dbLang->set('unicode', 'مرحبا 你好 こんにちは 🌍');

    expect($dbLang->get('unicode'))->toBe('مرحبا 你好 こんにちは 🌍');
});

test('set can save with label', function () {
    $dbLang = new DbLang;

    $dbLang->set('welcome', 'Welcome!', 'Welcome Message');

    $language = Language::where('language_code', 'en')->where('key', 'welcome')->first();

    expect($language->value)->toBe('Welcome!')
        ->and($language->label)->toBe('Welcome Message');
});

test('set without label does not overwrite existing label', function () {
    Language::create([
        'language_code' => 'en',
        'key' => 'greeting',
        'value' => 'Old Greeting',
        'label' => 'Original Label',
    ]);

    $dbLang = new DbLang;
    $dbLang->set('greeting', 'New Greeting');

    $language = Language::where('language_code', 'en')->where('key', 'greeting')->first();

    expect($language->value)->toBe('New Greeting')
        ->and($language->label)->toBe('Original Label');
});

test('set with null label explicitly does not update label', function () {
    Language::create([
        'language_code' => 'en',
        'key' => 'labeled',
        'value' => 'value',
        'label' => 'Existing Label',
    ]);

    $dbLang = new DbLang;
    $dbLang->set('labeled', 'new value', null);

    $language = Language::where('language_code', 'en')->where('key', 'labeled')->first();

    expect($language->value)->toBe('new value')
        ->and($language->label)->toBe('Existing Label');
});

test('different language instances maintain separate caches', function () {
    Language::create(['language_code' => 'en', 'key' => 'test', 'value' => 'English']);
    Language::create(['language_code' => 'id', 'key' => 'test', 'value' => 'Indonesian']);

    $englishLang = new DbLang('en');
    $indonesianLang = new DbLang('id');

    expect($englishLang->get('test'))->toBe('English')
        ->and($indonesianLang->get('test'))->toBe('Indonesian');
});
