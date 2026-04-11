<?php

use Illuminate\Database\QueryException;
use VmEngine\Synapse\Models\Language;

test('can create language', function () {
    $language = Language::create([
        'language_code' => 'en',
        'key' => 'welcome',
        'value' => 'Welcome to our application',
        'label' => 'Welcome Message',
    ]);

    expect($language)->toBeInstanceOf(Language::class)
        ->and($language->language_code)->toBe('en')
        ->and($language->key)->toBe('welcome')
        ->and($language->value)->toBe('Welcome to our application')
        ->and($language->label)->toBe('Welcome Message');
});

test('can explicitly set language_code', function () {
    $language = Language::create([
        'language_code' => 'es',
        'key' => 'test',
        'value' => 'Test Value',
    ]);

    expect($language->language_code)->toBe('es');
});

test('value can be null', function () {
    $language = Language::create([
        'language_code' => 'en',
        'key' => 'nullable',
        'value' => null,
    ]);

    expect($language->value)->toBeNull();
});

test('label can be null', function () {
    $language = Language::create([
        'language_code' => 'en',
        'key' => 'no_label',
        'value' => 'Some translation',
    ]);

    expect($language->label)->toBeNull();
});

test('can query by language code and key', function () {
    Language::create(['language_code' => 'en', 'key' => 'greeting', 'value' => 'Hello']);
    Language::create(['language_code' => 'id', 'key' => 'greeting', 'value' => 'Halo']);

    $english = Language::where('language_code', 'en')->where('key', 'greeting')->first();
    $indonesian = Language::where('language_code', 'id')->where('key', 'greeting')->first();

    expect($english->value)->toBe('Hello')
        ->and($indonesian->value)->toBe('Halo');
});

test('unique constraint on language_code and key', function () {
    Language::create([
        'language_code' => 'en',
        'key' => 'test',
        'value' => 'First',
    ]);

    expect(fn () => Language::create([
        'language_code' => 'en',
        'key' => 'test',
        'value' => 'Second',
    ]))->toThrow(QueryException::class);
});

test('allows same key in different languages', function () {
    Language::create(['language_code' => 'en', 'key' => 'hello', 'value' => 'Hello']);
    Language::create(['language_code' => 'id', 'key' => 'hello', 'value' => 'Halo']);
    Language::create(['language_code' => 'es', 'key' => 'hello', 'value' => 'Hola']);

    expect(Language::where('key', 'hello')->count())->toBe(3);
});

test('updateOrCreate creates new language', function () {
    $language = Language::updateOrCreate(
        ['language_code' => 'en', 'key' => 'new.translation'],
        ['value' => 'New Translation']
    );

    expect($language->wasRecentlyCreated)->toBeTrue()
        ->and($language->value)->toBe('New Translation');
});

test('updateOrCreate updates existing language', function () {
    Language::create([
        'language_code' => 'en',
        'key' => 'existing',
        'value' => 'Old Translation',
    ]);

    $language = Language::updateOrCreate(
        ['language_code' => 'en', 'key' => 'existing'],
        ['value' => 'New Translation']
    );

    expect($language->wasRecentlyCreated)->toBeFalse()
        ->and($language->value)->toBe('New Translation')
        ->and(Language::where('language_code', 'en')->where('key', 'existing')->count())->toBe(1);
});

test('has timestamps', function () {
    $language = Language::create([
        'language_code' => 'en',
        'key' => 'timestamp.test',
        'value' => 'value',
    ]);

    expect($language->created_at)->not->toBeNull()
        ->and($language->updated_at)->not->toBeNull();
});

test('can fetch all languages for specific language code', function () {
    Language::create(['language_code' => 'en', 'key' => 'lang1', 'value' => 'Value 1']);
    Language::create(['language_code' => 'en', 'key' => 'lang2', 'value' => 'Value 2']);
    Language::create(['language_code' => 'id', 'key' => 'lang1', 'value' => 'Nilai 1']);

    $englishLanguages = Language::where('language_code', 'en')->get();

    expect($englishLanguages)->toHaveCount(2)
        ->and($englishLanguages->pluck('key')->toArray())->toContain('lang1', 'lang2');
});

test('supports dotted key notation', function () {
    Language::create(['language_code' => 'en', 'key' => 'menu.home', 'value' => 'Home']);
    Language::create(['language_code' => 'en', 'key' => 'menu.about', 'value' => 'About']);

    $languages = Language::where('language_code', 'en')->where('key', 'like', 'menu.%')->get();

    expect($languages)->toHaveCount(2);
});

test('can store unicode characters', function () {
    $unicodeValue = 'مرحبا 你好 こんにちは 🌍';

    $language = Language::create([
        'language_code' => 'ar',
        'key' => 'unicode.test',
        'value' => $unicodeValue,
    ]);

    expect($language->value)->toBe($unicodeValue);
});

test('can store long text values', function () {
    $longText = str_repeat('Lorem ipsum dolor sit amet. ', 1000);

    $language = Language::create([
        'language_code' => 'en',
        'key' => 'long.text',
        'value' => $longText,
    ]);

    expect($language->value)->toBe($longText);
});

test('can store HTML content', function () {
    $htmlContent = '<p>This is <strong>HTML</strong> content with <a href="#">links</a></p>';

    $language = Language::create([
        'language_code' => 'en',
        'key' => 'html.content',
        'value' => $htmlContent,
    ]);

    expect($language->value)->toBe($htmlContent);
});
