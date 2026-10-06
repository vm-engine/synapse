<?php

declare(strict_types=1);

use Illuminate\Foundation\Vite;
use Illuminate\Support\Facades\File;
use VmEngine\Synapse\Services\ThemeService;

beforeEach(function () {
    $this->service = new ThemeService;
});

/**
 * Force Vite::asset()/__invoke() to behave as if no manifest was built,
 * regardless of whether a real public/build/manifest.json exists on disk
 * (e.g. after running `npm run build` locally). Deterministic stand-in
 * for "Vite manifest is absent".
 */
function fakeMissingViteManifest(): void
{
    app()->instance(Vite::class, (new Vite)->useManifestFilename('does-not-exist.json'));
}

// ─── renderStyles / renderScripts — CDN only (no Vite manifest) ───────────────

test('renderStyles returns empty string when no CDN resources and no Vite manifest', function () {
    config(['synapps.apps.themes.css' => []]);
    fakeMissingViteManifest();

    $output = $this->service->renderStyles('app');

    // No CDN resources, Vite manifest not present — output should be empty
    expect($output)->toBe('');
});

test('renderScripts returns empty string when no CDN resources and no Vite manifest', function () {
    config(['synapps.apps.themes.js' => []]);
    fakeMissingViteManifest();

    $output = $this->service->renderScripts('app');

    expect($output)->toBe('');
});

test('renderStyles returns CDN link tags from config', function () {
    config([
        'synapps.apps.themes.css' => [
            'all' => ['https://cdn.example.com/global.css'],
            'app' => ['https://cdn.example.com/app.css'],
        ],
    ]);

    $output = $this->service->renderStyles('app');

    expect($output)->toContain('<link rel="stylesheet" href="https://cdn.example.com/global.css">')
        ->and($output)->toContain('<link rel="stylesheet" href="https://cdn.example.com/app.css">');
});

test('renderScripts returns CDN script tags from config', function () {
    config([
        'synapps.apps.themes.js' => [
            'all' => ['https://cdn.example.com/global.js'],
            'frontend' => ['https://cdn.example.com/frontend.js'],
        ],
    ]);

    $output = $this->service->renderScripts('frontend');

    expect($output)->toContain('<script src="https://cdn.example.com/global.js"></script>')
        ->and($output)->toContain('<script src="https://cdn.example.com/frontend.js"></script>');
});

test('renderScripts renders integrity and crossorigin attributes for object entries', function () {
    config([
        'synapps.apps.themes.js' => [
            'all' => [],
            'app' => [
                [
                    'src' => 'https://cdn.jsdelivr.net/npm/apexcharts@3.49.1/dist/apexcharts.min.js',
                    'integrity' => 'sha384-LoktLSu+KN7O05Gi2Epno0XhcstpeXAu40T7IP2JC5OZ2qKi/oE9znxViQS3yYn5',
                    'crossorigin' => 'anonymous',
                ],
            ],
        ],
    ]);

    $output = $this->service->renderScripts('app');

    expect($output)->toContain('src="https://cdn.jsdelivr.net/npm/apexcharts@3.49.1/dist/apexcharts.min.js"')
        ->and($output)->toContain('integrity="sha384-LoktLSu+KN7O05Gi2Epno0XhcstpeXAu40T7IP2JC5OZ2qKi/oE9znxViQS3yYn5"')
        ->and($output)->toContain('crossorigin="anonymous"');
});

test('renderStyles renders integrity and crossorigin attributes for object entries', function () {
    config([
        'synapps.apps.themes.css' => [
            'all' => [],
            'app' => [
                [
                    'href' => 'https://cdn.example.com/lib.css',
                    'integrity' => 'sha384-abc123',
                    'crossorigin' => 'anonymous',
                ],
            ],
        ],
    ]);

    $output = $this->service->renderStyles('app');

    expect($output)->toContain('href="https://cdn.example.com/lib.css"')
        ->and($output)->toContain('integrity="sha384-abc123"')
        ->and($output)->toContain('crossorigin="anonymous"');
});

test('renderScripts still supports plain string entries without integrity', function () {
    config([
        'synapps.apps.themes.js' => [
            'all' => ['https://cdn.example.com/plain.js'],
        ],
    ]);
    fakeMissingViteManifest();

    $output = $this->service->renderScripts('app');

    expect($output)->toBe('<script src="https://cdn.example.com/plain.js"></script>');
});

test('renderStyles does not include layout-specific CDN for different layout', function () {
    config([
        'synapps.apps.themes.css' => [
            'all' => ['https://cdn.example.com/global.css'],
            'app' => ['https://cdn.example.com/app-only.css'],
            'guest' => ['https://cdn.example.com/guest-only.css'],
        ],
    ]);

    $output = $this->service->renderStyles('frontend');

    expect($output)->toContain('global.css')
        ->and($output)->not->toContain('app-only.css')
        ->and($output)->not->toContain('guest-only.css');
});

// ─── Vite fallback: missing manifest entries are skipped silently ─────────────

test('renderViteStyles silently returns empty string when Vite manifest is absent', function () {
    // Vite::asset() will throw when no manifest is built
    $output = $this->service->renderStyles('app');

    // Should not throw — CDN output (empty) + silently skipped Vite
    expect($output)->toBeString();
});

test('renderViteScripts silently returns empty string when Vite manifest is absent', function () {
    $output = $this->service->renderScripts('guest');

    expect($output)->toBeString();
});

// ─── getCurrentLayoutType ─────────────────────────────────────────────────────

test('getCurrentLayoutType returns frontend for root path', function () {
    $this->get('/');

    $layoutType = $this->service->getCurrentLayoutType();

    expect($layoutType)->toBe('frontend');
});

test('getCurrentLayoutType returns app for admin-prefixed path', function () {
    config(['synapps.auth.routes.backend_prefix' => 'admin']);

    $this->get('/admin/dashboard');

    $layoutType = $this->service->getCurrentLayoutType();

    expect($layoutType)->toBe('app');
});

test('getCurrentLayoutType returns frontend when path does not match backend prefix', function () {
    config(['synapps.auth.routes.backend_prefix' => 'admin']);

    $this->get('/blog/post');

    $layoutType = $this->service->getCurrentLayoutType();

    expect($layoutType)->toBe('frontend');
});

// ─── resolveThemeLayout ───────────────────────────────────────────────────────

beforeEach(function () {
    // resolveThemeLayout()'s fallback tier checks synapps_path() on disk. Real
    // installs get the "default" theme there via `synapps:setup` (vendor:publish
    // of stubs/themes/default), but Testbench's bare base path has no such
    // install — and other parallel test files (e.g. SynAppsPatchResourcesSplitTest)
    // create/delete base_path('synapps') themselves, so reusing the "default"
    // theme name here would race across parallel processes. Use a dedicated
    // fixture theme name and point the fallback config at it instead.
    $this->fallbackThemeName = 'resolve-theme-layout-fallback-fixture';
    config(['synapps.apps.themes.fallback' => $this->fallbackThemeName]);

    $this->fallbackThemePath = synapps_path("resources/themes/{$this->fallbackThemeName}/layouts");
    File::ensureDirectoryExists($this->fallbackThemePath);
    foreach (['app', 'frontend', 'guest'] as $layout) {
        File::put("{$this->fallbackThemePath}/{$layout}.blade.php", '');
    }
});

afterEach(function () {
    File::deleteDirectory(synapps_path("resources/themes/{$this->fallbackThemeName}"));
});

test('resolveThemeLayout uses fallback theme when active theme layout file missing', function () {
    // Fallback theme config points at the fixture theme created in beforeEach,
    // whose layout files genuinely exist on disk — so the middle tier of the
    // chain (theme-fallback::) is what actually resolves here, not the
    // ultimate synapps:: default.
    config(['synapps.apps.themes.active' => 'nonexistent-theme']);

    $view = $this->service->resolveThemeLayout('app');

    expect($view)->toBe('theme-fallback::app');
});

test('resolveThemeLayout falls back to synapps default when both active and fallback theme layout files missing', function () {
    config(['synapps.apps.themes.active' => 'nonexistent-theme']);
    config(['synapps.apps.themes.fallback' => 'also-nonexistent-theme']);

    $view = $this->service->resolveThemeLayout('app');

    expect($view)->toBe('synapps::components.layouts.app');
});

test('resolveThemeLayout uses fallback theme for frontend layout', function () {
    config(['synapps.apps.themes.active' => 'nonexistent-theme']);

    $view = $this->service->resolveThemeLayout('frontend');

    expect($view)->toBe('theme-fallback::frontend');
});

test('resolveThemeLayout falls back to synapps default for frontend layout when both tiers missing', function () {
    config(['synapps.apps.themes.active' => 'nonexistent-theme']);
    config(['synapps.apps.themes.fallback' => 'also-nonexistent-theme']);

    $view = $this->service->resolveThemeLayout('frontend');

    expect($view)->toBe('synapps::components.layouts.layout');
});

test('resolveThemeLayout uses fallback theme for guest layout', function () {
    config(['synapps.apps.themes.active' => 'nonexistent-theme']);

    $view = $this->service->resolveThemeLayout('guest');

    expect($view)->toBe('theme-fallback::guest');
});

test('resolveThemeLayout falls back to synapps default for guest layout when both tiers missing', function () {
    config(['synapps.apps.themes.active' => 'nonexistent-theme']);
    config(['synapps.apps.themes.fallback' => 'also-nonexistent-theme']);

    $view = $this->service->resolveThemeLayout('guest');

    expect($view)->toBe('synapps::components.layouts.guest');
});

// ─── getActiveTheme ───────────────────────────────────────────────────────────

test('getActiveTheme returns default from config', function () {
    config(['synapps.apps.themes.active' => 'my-theme']);

    expect($this->service->getActiveTheme())->toBe('my-theme');
});

test('getActiveTheme falls back to default when config not set', function () {
    config(['synapps.apps.themes' => []]);

    expect($this->service->getActiveTheme())->toBe('default');
});

// ─── getThemePath ─────────────────────────────────────────────────────────────

test('getThemePath returns path to active theme directory', function () {
    config(['synapps.apps.themes.active' => 'my-theme']);

    $path = $this->service->getThemePath();

    expect($path)->toEndWith('resources/themes/my-theme');
});

test('getThemePath returns path to specific theme file', function () {
    config(['synapps.apps.themes.active' => 'default']);

    $path = $this->service->getThemePath('css/theme-base.css');

    expect($path)->toEndWith('resources/themes/default/css/theme-base.css');
});

test('getThemePath accepts explicit theme override', function () {
    $path = $this->service->getThemePath('css/theme.css', 'application');

    expect($path)->toContain('resources/themes/application/css/theme.css');
});
