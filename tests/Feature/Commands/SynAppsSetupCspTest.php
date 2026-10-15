<?php

declare(strict_types=1);

use VmEngine\Synapse\Console\Commands\SynAppsSetup;

it('defines every CSP directive SecureHeaders reads', function () {
    expect(array_keys(SynAppsSetup::DEFAULT_CSP))->toBe([
        'script_src', 'style_src', 'font_src', 'img_src', 'connect_src', 'frame_src', 'media_src',
    ]);
});

it('allows the Google Fonts hosts the stub themes load', function (string $theme) {
    $themeJson = file_get_contents(dirname(__DIR__, 3)."/stubs/themes/{$theme}/theme.json");

    expect($themeJson)->toContain('https://fonts.googleapis.com')
        ->and(SynAppsSetup::DEFAULT_CSP['style_src'])->toContain('https://fonts.googleapis.com')
        ->and(SynAppsSetup::DEFAULT_CSP['font_src'])->toContain('https://fonts.gstatic.com');
})->with(['default', 'application']);

it('allows the toastr notification sound host', function () {
    $components = file_get_contents(dirname(__DIR__, 3).'/stubs/synapps/resources/js/synapse-components.js');

    expect($components)->toContain('https://res.cloudinary.com/')
        ->and(SynAppsSetup::DEFAULT_CSP['media_src'])->toContain('https://res.cloudinary.com');
});
