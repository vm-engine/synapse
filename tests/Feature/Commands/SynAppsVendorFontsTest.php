<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->themeName = 'vendor-fonts-test-theme';
    $this->themePath = synapps_path("resources/themes/{$this->themeName}");

    File::ensureDirectoryExists($this->themePath);

    File::put("{$this->themePath}/theme.json", json_encode([
        'id' => $this->themeName,
        'css' => ['all' => [], 'frontend' => []],
        'js' => ['all' => [], 'frontend' => []],
        'headers' => [
            'all' => [],
            'frontend' => [
                "<link rel='preconnect' href='https://fonts.googleapis.com'>",
                "<link rel='preconnect' href='https://fonts.gstatic.com' crossorigin>",
                "<link href='https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap' rel='stylesheet'>",
            ],
        ],
    ], JSON_PRETTY_PRINT));
});

afterEach(function () {
    File::deleteDirectory($this->themePath);
});

it('fails when the theme does not exist', function () {
    $this->artisan('synapps:vendor-fonts', ['theme' => 'does-not-exist'])
        ->assertExitCode(1);
});

it('fails when theme.json has no google fonts link', function () {
    File::put("{$this->themePath}/theme.json", json_encode([
        'id' => $this->themeName,
        'css' => ['all' => []],
        'js' => ['all' => []],
        'headers' => ['all' => []],
    ]));

    $this->artisan('synapps:vendor-fonts', ['theme' => $this->themeName])
        ->assertExitCode(1);
});

it('downloads fonts, rewrites the CSS locally, and updates theme.json', function () {
    Http::fake([
        'fonts.googleapis.com/*' => Http::response(
            "@font-face{font-family:'Inter';src:url(https://fonts.gstatic.com/s/inter/v1/abcdef.woff2) format('woff2');}",
            200
        ),
        'fonts.gstatic.com/*' => Http::response('FAKE_WOFF2_BYTES', 200),
    ]);

    $this->artisan('synapps:vendor-fonts', ['theme' => $this->themeName])
        ->assertExitCode(0);

    $fontsDir = "{$this->themePath}/assets/fonts";
    expect(File::isDirectory($fontsDir))->toBeTrue();

    $woff2Files = collect(File::files($fontsDir))
        ->filter(fn ($f) => $f->getExtension() === 'woff2');
    expect($woff2Files)->toHaveCount(1);

    $cssContent = File::get("{$fontsDir}/fonts.css");
    expect($cssContent)->not->toContain('fonts.gstatic.com')
        ->and($cssContent)->toContain('.woff2');

    $themeJson = json_decode(File::get("{$this->themePath}/theme.json"), true);
    expect($themeJson['headers']['frontend'])->toBeEmpty()
        ->and($themeJson['css']['frontend'])->toContain("/assets/themes/{$this->themeName}/fonts/fonts.css");
});

it('does not modify theme.json or write any files when a font download fails', function () {
    Http::fake([
        'fonts.googleapis.com/*' => Http::response(
            "@font-face{font-family:'Inter';src:url(https://fonts.gstatic.com/s/inter/v1/abcdef.woff2) format('woff2');}",
            200
        ),
        'fonts.gstatic.com/*' => Http::response('', 500),
    ]);

    $originalThemeJson = File::get("{$this->themePath}/theme.json");

    $this->artisan('synapps:vendor-fonts', ['theme' => $this->themeName])
        ->assertExitCode(1);

    expect(File::get("{$this->themePath}/theme.json"))->toBe($originalThemeJson);
    expect(File::isDirectory("{$this->themePath}/assets/fonts"))->toBeFalse();
});

it('does not duplicate the fonts.css entry when re-run after the header is re-added', function () {
    Http::fake([
        'fonts.googleapis.com/*' => Http::response(
            "@font-face{font-family:'Inter';src:url(https://fonts.gstatic.com/s/inter/v1/abcdef.woff2) format('woff2');}",
            200
        ),
        'fonts.gstatic.com/*' => Http::response('FAKE_WOFF2_BYTES', 200),
    ]);

    $this->artisan('synapps:vendor-fonts', ['theme' => $this->themeName])->assertExitCode(0);

    // Simulate someone manually re-adding the Google Fonts header, then re-run.
    $themeJson = json_decode(File::get("{$this->themePath}/theme.json"), true);
    $themeJson['headers']['frontend'][] = "<link href='https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap' rel='stylesheet'>";
    File::put("{$this->themePath}/theme.json", json_encode($themeJson, JSON_PRETTY_PRINT));

    $this->artisan('synapps:vendor-fonts', ['theme' => $this->themeName])->assertExitCode(0);

    $finalThemeJson = json_decode(File::get("{$this->themePath}/theme.json"), true);
    $fontsCssCount = collect($finalThemeJson['css']['frontend'])
        ->filter(fn ($e) => $e === "/assets/themes/{$this->themeName}/fonts/fonts.css")
        ->count();
    expect($fontsCssCount)->toBe(1);
});
