<?php

use VmEngine\Synapse\Services\ThemeService;

beforeEach(function () {
    $this->service = new ThemeService;
});

test('renderStyles returns empty string when no CDN resources', function () {
    config(['synapps.apps.themes.css' => []]);

    $output = $this->service->renderStyles('app');

    expect($output)->toBe('');
});

test('renderScripts returns empty string when no CDN resources', function () {
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

    $output = $this->service->renderScripts('app');

    expect($output)->toBe('<script src="https://cdn.example.com/plain.js"></script>');
});

test('renderStyles does not include layout-specific CDN for different layout', function () {
    config([
        'synapps.apps.themes.css' => [
            'all' => [],
            'app' => ['https://cdn.example.com/app-only.css'],
            'frontend' => ['https://cdn.example.com/frontend-only.css'],
        ],
    ]);

    $output = $this->service->renderStyles('frontend');

    expect($output)->toContain('frontend-only.css')
        ->and($output)->not->toContain('app-only.css');
});

test('getCurrentLayoutType returns frontend for root path', function () {
    $this->get('/');

    expect($this->service->getCurrentLayoutType())->toBe('frontend');
});

test('getActiveTheme returns default from config', function () {
    config(['synapps.apps.themes.active' => 'custom-theme']);

    expect($this->service->getActiveTheme())->toBe('custom-theme');
});

test('getActiveTheme falls back to default when config not set', function () {
    config(['synapps.apps.themes' => []]);

    expect($this->service->getActiveTheme())->toBe('default');
});
