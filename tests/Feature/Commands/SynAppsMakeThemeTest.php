<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->sourcePath = synapps_path('resources/themes/make-theme-source');
    $this->themePath = synapps_path('resources/themes/make-theme-copy');

    File::ensureDirectoryExists("{$this->sourcePath}/css");
    File::put("{$this->sourcePath}/theme.json", json_encode(['id' => 'make-theme-source', 'name' => 'Source']));
    File::put("{$this->sourcePath}/css/theme-base.css", '/* base */');
});

afterEach(function () {
    File::deleteDirectory($this->sourcePath);
    File::deleteDirectory($this->themePath);
});

it('copies every file of the source theme into the new theme', function () {
    $this->artisan('synapps:make-theme', ['name' => 'make-theme-copy', '--copy-from' => 'make-theme-source'])
        ->assertSuccessful();

    expect(File::get("{$this->themePath}/css/theme-base.css"))->toBe('/* base */')
        ->and("{$this->themePath}/theme.json")->toBeFile();
});
