<?php

use VmEngine\Synapse\Services\ThemeService;

beforeEach(function () {
    $this->service = new ThemeService;
});

// ─── renderStyles / renderScripts — CDN only (no Vite manifest) ───────────────

test('renderStyles returns empty string when no CDN resources and no Vite manifest', function () {
    config(['synapps.apps.themes.css' => []]);

    $output = $this->service->renderStyles('app');

    // No CDN resources, Vite manifest not present — output should be empty
    expect($output)->toBe('');
});

test('renderScripts returns empty string when no CDN resources and no Vite manifest', function () {
    config(['synapps.apps.themes.js' => []]);

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

test('resolveThemeLayout falls back to synapps default when theme layout file missing', function () {
    config(['synapps.apps.themes.active' => 'nonexistent-theme']);

    $view = $this->service->resolveThemeLayout('app');

    expect($view)->toBe('synapps::components.layouts.app');
});

test('resolveThemeLayout falls back to synapps default for frontend layout', function () {
    config(['synapps.apps.themes.active' => 'nonexistent-theme']);

    $view = $this->service->resolveThemeLayout('frontend');

    expect($view)->toBe('synapps::components.layouts.layout');
});

test('resolveThemeLayout falls back to synapps default for guest layout', function () {
    config(['synapps.apps.themes.active' => 'nonexistent-theme']);

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
