<?php

declare(strict_types=1);

use Illuminate\Foundation\Vite;
use Illuminate\Support\Facades\File;
use Illuminate\Support\HtmlString;

/**
 * Contract tests for the bundled theme stubs (packages/synapse/stubs/themes/*).
 */
function themeStubPath(string $theme, string $path = ''): string
{
    return dirname(__DIR__, 3)."/stubs/themes/{$theme}".($path !== '' ? '/'.$path : '');
}

/**
 * Render a stub layout file with Vite forced into "no manifest" mode so
 *
 * @themeStyles/@themeScripts output stays deterministic.
 *
 * @param  array<string, mixed>  $data
 */
function renderThemeLayout(string $theme, string $layout, array $data = []): string
{
    app()->instance(Vite::class, (new Vite)->useManifestFilename('does-not-exist.json'));

    return view()->file(
        themeStubPath($theme, "layouts/{$layout}.blade.php"),
        array_merge(['slot' => new HtmlString('<p>page-body</p>')], $data)
    )->render();
}

/**
 * @return array<int, string>
 */
function themeStubFiles(string $theme, string $extension): array
{
    return collect(File::allFiles(themeStubPath($theme)))
        ->filter(fn ($file) => str_ends_with($file->getFilename(), $extension))
        ->map(fn ($file) => $file->getPathname())
        ->values()
        ->all();
}

dataset('themes', ['default', 'application']);

test('layouts do not hardcode Google Fonts or call @vite', function (string $theme, string $layout) {
    $source = (string) file_get_contents(themeStubPath($theme, "layouts/{$layout}.blade.php"));

    expect($source)->not->toContain('fonts.googleapis.com')
        ->and($source)->not->toContain('fonts.gstatic.com')
        ->and($source)->not->toContain('@vite(');
})->with('themes')->with(['app', 'guest', 'frontend']);

// Livewire's wire:navigate waits only for new <head> scripts before starting Alpine on the
// new page; body scripts are re-run but not awaited. A layout entry loaded from <body> would
// register its Alpine components too late after a navigate from a different layout.
test('layouts load their theme scripts inside <head>', function (string $theme, string $layout) {
    $source = (string) file_get_contents(themeStubPath($theme, "layouts/{$layout}.blade.php"));

    expect(substr_count($source, '@themeScripts('))->toBe(1)
        ->and(strpos($source, '@themeScripts('))->toBeLessThan(strpos($source, '</head>'));

    // @livewireScripts (when a layout renders it rather than relying on auto-injection) stays in <body>.
    if (str_contains($source, '@livewireScripts')) {
        expect(strpos($source, '@livewireScripts'))->toBeGreaterThan(strpos($source, '</head>'));
    }
})->with('themes')->with(['app', 'guest', 'frontend']);

test('synapps fallback layouts load their js entry inside <head>', function (string $file) {
    $source = (string) file_get_contents(dirname(__DIR__, 3).'/stubs/synapps/resources/views/'.$file);
    $head = substr($source, 0, (int) strpos($source, '</head>'));

    expect($head)->toMatch('/@themeScripts\(|@vite\(\[[^\]]*resources\/js\/app\.js/')
        ->and(substr($source, (int) strpos($source, '</head>')))->not->toMatch('/@themeScripts\(|@vite\(\[[^\]]*resources\/js\//');
})->with([
    'components/layouts/app.blade.php',
    'components/layouts/layout.blade.php',
    'components/layouts/app-bootstrap.blade.php',
    'layouts/app.blade.php',
]);

// synapse-components.js (synapseAdvSelect, synapseDatepicker, toastr, …) is imported by the base
// entry since the per-layout split; a layout that loads only app.js has none of those components.
test('synapps fallback layouts load the base entry as well as app', function (string $file) {
    $source = (string) file_get_contents(dirname(__DIR__, 3).'/stubs/synapps/resources/views/'.$file);

    if (str_contains($source, '@themeScripts(')) {
        expect($source)->toContain('@themeStyles(');

        return;
    }

    expect($source)->toContain('resources/js/base.js');

    // The Tailwind layout also needs the base stylesheet; the Bootstrap layouts keep their own CSS
    // (base.css carries Tailwind's preflight, which would restyle a Bootstrap page).
    if (! str_contains($file, 'bootstrap')) {
        expect($source)->toContain('resources/css/base.css');
    }
})->with([
    'components/layouts/app.blade.php',
    'components/layouts/layout.blade.php',
    'components/layouts/app-bootstrap.blade.php',
    'layouts/app.blade.php',
]);

test('stub layouts rendering @themeHeaders do not also hardcode Google Fonts', function () {
    $offenders = collect(File::allFiles(dirname(__DIR__, 3).'/stubs'))
        ->filter(fn ($file) => str_ends_with($file->getFilename(), '.blade.php'))
        ->filter(fn ($file) => str_contains($file->getContents(), '@themeHeaders')
            && str_contains($file->getContents(), 'fonts.googleapis.com'))
        ->map(fn ($file) => $file->getRelativePathname())
        ->values()
        ->all();

    expect($offenders)->toBe([]);
});

test('theme css does not import fonts', function (string $theme) {
    foreach (themeStubFiles($theme, '.css') as $path) {
        expect((string) file_get_contents($path))->not->toContain('@import url(');
    }
})->with('themes');

test('layouts register no Alpine components inline', function (string $theme) {
    foreach (themeStubFiles($theme, '.blade.php') as $path) {
        expect((string) file_get_contents($path))->not->toContain('Alpine.data(');
    }
})->with('themes');

test('theme carries no project-specific branding', function (string $theme) {
    foreach (File::allFiles(themeStubPath($theme)) as $file) {
        // Covers split SVG text too, e.g. "Member<tspan>4VM</tspan>".
        expect($file->getContents())->not->toContain('Member4VM')
            ->and($file->getContents())->not->toContain('m4vm')
            ->and($file->getContents())->not->toContain('4VM');
    }
})->with('themes');

test('theme.json declares fonts in headers.all and all three layouts', function (string $theme) {
    $config = json_decode((string) file_get_contents(themeStubPath($theme, 'theme.json')), true);
    $headers = implode("\n", $config['headers']['all']);

    expect($headers)->toContain('https://fonts.googleapis.com/css2?family=')
        ->and($headers)->toContain('rel="preconnect"')
        ->and($headers)->not->toContain('Cormorant')
        ->and($config['layouts'] ?? ['app', 'guest', 'frontend'])->toContain('frontend')
        ->and(implode("\n", $config['css']['all'] ?? []))->not->toContain('font-awesome');
})->with('themes');

test('frontend layout uses app description by default', function (string $theme) {
    config(['synapps.apps.description' => 'App wide description']);

    $html = renderThemeLayout($theme, 'frontend');

    expect($html)->toMatch('/<meta\s+name="description"\s+content="App wide description"\s*>/')
        ->and($html)->toContain('<p>page-body</p>');
})->with('themes');

test('frontend layout accepts title and description overrides', function (string $theme) {
    $html = renderThemeLayout($theme, 'frontend', [
        'title' => 'Article Title',
        'description' => 'Article "summary"',
    ]);

    expect($html)->toContain('<title>Article Title</title>')
        ->and($html)->toContain('content="Article &quot;summary&quot;"');
})->with('themes');

test('frontend layout renders pushed head and nav stacks', function (string $theme) {
    app('view')->startPush('head', '<link rel="alternate" hreflang="id" href="/id">');
    app('view')->startPush('nav', '<a class="pushed-nav" href="/news">News</a>');

    $html = renderThemeLayout($theme, 'frontend');

    expect($html)->toContain('<link rel="alternate" hreflang="id" href="/id">')
        ->and($html)->toContain('<a class="pushed-nav" href="/news">News</a>');
})->with('themes');

test('frontend layout wires the frontendHeader component', function (string $theme) {
    $html = renderThemeLayout($theme, 'frontend');

    expect($html)->toContain('x-data="frontendHeader"')
        ->and($html)->toContain('aria-controls="fe-nav"')
        // btn-icon opts out of theme-base's global button[type=button] reset,
        // which otherwise out-specifies .fe-mobile-toggle and keeps it visible on desktop.
        ->and($html)->toContain('class="fe-mobile-toggle btn-icon"');
})->with('themes');

test('frontend layout renders without auth routes', function (string $theme) {
    // No auth.login / backend.home.dashboard routes exist here; an unguarded route() would throw.
    expect(renderThemeLayout($theme, 'frontend'))->toContain('</html>');
})->with('themes');

test('frontend layout strings are translatable', function (string $theme) {
    $langPath = sys_get_temp_dir().'/synapse-theme-lang-'.uniqid();
    mkdir($langPath);
    file_put_contents($langPath.'/id.json', json_encode([
        'Sign in' => 'Masuk',
        'All rights reserved.' => 'Hak cipta dilindungi.',
    ]));
    app('translator')->getLoader()->addJsonPath($langPath);
    app()->setLocale('id');
    app('router')->get('/login', fn () => 'login')->name('auth.login');
    app('router')->getRoutes()->refreshNameLookups();

    $html = renderThemeLayout($theme, 'frontend');
    File::deleteDirectory($langPath);

    expect($html)->toContain('Masuk')
        ->and($html)->toContain('Hak cipta dilindungi.');
})->with('themes');

test('guest layout renders the slot with the app title', function (string $theme) {
    config(['synapps.apps.title' => 'Acme Portal']);

    $html = renderThemeLayout($theme, 'guest');

    expect($html)->toContain('<p>page-body</p>')
        ->and($html)->toContain('Acme Portal');
})->with('themes');

// alpine:init fires once per full page load. After a wire:navigate from another layout the
// layout's entry loads with Alpine already running, so an alpine:init-only registration never runs.
test('theme JS registers its Alpine components even when Alpine is already running', function (string $theme) {
    foreach (themeStubFiles($theme, '.js') as $file) {
        $js = (string) file_get_contents($file);

        if (! str_contains($js, 'Alpine.data(')) {
            continue;
        }

        // An anonymous alpine:init callback can never also run right away, so every block must be
        // a named register function called directly when window.Alpine already exists.
        expect(preg_match('/addEventListener\(\s*[\'"]alpine:init[\'"]\s*,\s*(\(|function)/', $js))->toBe(0, basename($file).' has an alpine:init-only block')
            ->and(str_contains($js, 'window.Alpine'))->toBeTrue(basename($file).' registers only on alpine:init');
    }
})->with('themes');

test('theme JS registers its layout shells', function (string $theme, string $file, string $component) {
    $js = (string) file_get_contents(themeStubPath($theme, "js/{$file}"));

    expect($js)->toContain("Alpine.data('{$component}'");
})->with([
    'default frontend' => ['default', 'theme-frontend.js', 'frontendHeader'],
    'application frontend' => ['application', 'theme-frontend.js', 'frontendHeader'],
    'application app' => ['application', 'theme-app.js', 'applicationAppShell'],
    'application guest + frontend' => ['application', 'theme-base.js', 'applicationThemeShell'],
]);

test('theme JS does not register Alpine components in the core synapse* namespace', function (string $theme) {
    // Theme JS loads on every page of its layout type, including pages rendered by core
    // fallback layouts — reusing a core name (e.g. synapseAppShell) silently replaces it.
    foreach (themeStubFiles($theme, '.js') as $path) {
        expect((string) file_get_contents($path))->not->toMatch("/Alpine\\.data\\(\\s*['\"]synapse/");
    }
})->with('themes');

test('application app layout uses the shared toastr and real header widgets', function () {
    $layout = (string) file_get_contents(themeStubPath('application', 'layouts/app.blade.php'));
    $header = (string) file_get_contents(themeStubPath('application', 'layouts/components/header-nav.blade.php'));

    expect($layout)->toContain('<x-synapse-toastr')
        ->and($layout)->not->toContain('x-data="toast"')
        ->and($header)->toContain('<livewire:synsearch::palette')
        ->and($header)->toContain('<livewire:synotif::bell')
        ->and($header)->toContain('<x-synapse::notification-panel')
        ->and($header)->not->toContain('placeholder="Search');
});

test('default theme keeps a grouped input fully bordered', function () {
    // Every .input-group-item (icon, prefix, button) is an absolute overlay inside the input,
    // so squaring the input's right side left icon/prefix inputs without a right edge.
    $css = File::get(themeStubPath('default', 'css/theme-base.css'));
    $rule = str($css)->after('.input-group > select {')->before('}')->toString();

    expect($rule)->toContain('flex: 1 1 0%')
        ->not->toContain('border-right-width')
        ->not->toContain('border-top-right-radius')
        ->not->toContain('border-bottom-right-radius');
});

test('default theme gives a plain .btn a border and fill, leaving variants and overlays alone', function () {
    $css = File::get(themeStubPath('default', 'css/theme-base.css'));
    $selector = '.btn:where(:not(.primary, .secondary, .success, .danger, .warning, .info, .input-group-item))';
    $rule = str($css)->after($selector.' {')->before('}')->toString();

    expect($css)->toContain($selector)
        ->and($rule)->toContain('border border-gray-300 bg-gray-50');
});
