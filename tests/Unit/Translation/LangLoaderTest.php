<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\TranslationServiceProvider;
use VmEngine\Synapse\SynapseLangServiceProvider;

// Laravel's own lang files only exist in English, and a Synapse app runs with APP_LOCALE and
// APP_FALLBACK_LOCALE set to another locale — so without these files every validation, auth,
// password and pagination message renders as its raw key (`validation.required`).

function synapseLangDir(): string
{
    return dirname(__DIR__, 3).'/lang';
}

function frameworkLangDir(): string
{
    return dirname((string) (new ReflectionClass(TranslationServiceProvider::class))->getFileName()).'/lang';
}

it('looks up group files in the framework, then Synapse, then the project', function () {
    expect(app('translation.loader')->paths())->toBe([
        frameworkLangDir(),
        synapseLangDir(),
        app()->langPath(),
    ]);
});

it('ships every framework message, with the same placeholders', function (string $locale, string $group) {
    // Messages only: the framework's empty `attributes` array flattens to a leaf of its own.
    $english = array_filter(Arr::dot(require frameworkLangDir()."/en/{$group}.php"), 'is_string');
    $shipped = Arr::dot(require synapseLangDir()."/{$locale}/{$group}.php");

    expect(array_diff_key($english, $shipped))->toBe([]);

    foreach ($english as $key => $message) {
        preg_match_all('/:[a-z_]+/', (string) $message, $expected);
        preg_match_all('/:[a-z_]+/', (string) $shipped[$key], $actual);
        $expectedPlaceholders = array_unique($expected[0]);
        $actualPlaceholders = array_unique($actual[0]);
        sort($expectedPlaceholders);
        sort($actualPlaceholders);

        expect($actualPlaceholders)->toBe($expectedPlaceholders, "{$locale}/{$group}.{$key}");
    }
})->with(fn () => collect(glob(synapseLangDir().'/*', GLOB_ONLYDIR) ?: [])
    ->flatMap(fn (string $dir) => collect(['auth', 'pagination', 'passwords', 'validation'])
        ->map(fn (string $group) => [basename($dir), $group]))
    ->all());

it('renders validation messages as sentences, not keys, when the app locale has no fallback to English', function () {
    $locale = basename((string) Arr::first(glob(synapseLangDir().'/*', GLOB_ONLYDIR) ?: []));
    app()->setLocale($locale);
    app()->setFallbackLocale($locale);

    $messages = Validator::make(['email' => 'not-an-email'], ['name' => 'required', 'email' => 'email'])->errors()->all();

    expect($messages)->toHaveCount(2)
        ->and($messages)->each(fn ($message) => $message->not->toStartWith('validation.'));
});

it('lets a project override a single message and keep the rest from Synapse', function () {
    $project = sys_get_temp_dir().'/synapse-lang-'.uniqid();
    $locale = basename((string) Arr::first(glob(synapseLangDir().'/*', GLOB_ONLYDIR) ?: []));
    mkdir("{$project}/{$locale}", 0777, true);
    file_put_contents("{$project}/{$locale}/validation.php", "<?php return ['required' => 'T-project-required', 'attributes' => ['name' => 'T-project-name']];");

    try {
        $lines = (new FileLoader(new Filesystem, SynapseLangServiceProvider::loaderPaths($project)))->load($locale, 'validation');
        $synapse = require synapseLangDir()."/{$locale}/validation.php";

        expect($lines['required'])->toBe('T-project-required')
            ->and($lines['attributes']['name'])->toBe('T-project-name')
            ->and($lines['email'])->toBe($synapse['email'])
            ->and($lines['attributes']['email'])->toBe($synapse['attributes']['email']);
    } finally {
        (new Filesystem)->deleteDirectory($project);
    }
});
