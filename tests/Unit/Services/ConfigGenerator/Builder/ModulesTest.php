<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use VmEngine\Synapse\Services\ConfigGenerator\Builder;
use VmEngine\Synapse\Services\ConfigGenerator\Builder\Modules;

/**
 * Directory modules are only compiled in when the root composer.json autoloads them.
 * A composer.json written on Windows (backslash paths) must still count on Linux and
 * vice versa, or a module committed from one OS silently disappears on the other.
 */
beforeEach(function () {
    $this->originalBasePath = base_path();
    $this->sandbox = sys_get_temp_dir().'/synapse-modules-builder-'.bin2hex(random_bytes(6));
    File::ensureDirectoryExists($this->sandbox.'/synapps/config/compiled');
    File::ensureDirectoryExists($this->sandbox.'/synapps/modules/home');
    file_put_contents($this->sandbox.'/synapps/modules/home/module.json', json_encode([
        'id' => 'home',
        'id_case' => ['pascal' => 'Home'],
    ]));
    app()->setBasePath($this->sandbox);
});

afterEach(function () {
    app()->setBasePath($this->originalBasePath);
    File::deleteDirectory($this->sandbox);
});

/**
 * @param  array<string, string>  $psr4
 * @return array<string, mixed>
 */
function compileModulesWith(array $psr4): array
{
    file_put_contents(base_path('composer.json'), json_encode(['autoload' => ['psr-4' => $psr4]], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    $builder = new Builder;
    $builder->config['modules'] = [];
    (new Modules($builder))->init();

    /** @var array<string, mixed> $modules */
    $modules = $builder->config['modules'];

    return $modules;
}

it('discovers a directory module autoloaded with forward-slash paths', function () {
    expect(compileModulesWith(['SynApps\\Modules\\Home\\' => 'synapps/modules/home/src/']))->toHaveKey('home');
});

it('discovers a directory module autoloaded with Windows backslash paths', function () {
    expect(compileModulesWith(['SynApps\\Modules\\Home\\' => 'synapps\\modules\\home\\src\\']))->toHaveKey('home');
});

it('skips a directory module the root composer.json does not autoload', function () {
    expect(compileModulesWith(['App\\' => 'app/']))->not->toHaveKey('home');
});

it('does not mistake a module whose id only prefixes another for that module', function () {
    expect(compileModulesWith(['SynApps\\Modules\\Homepage\\' => 'synapps/modules/homepage/src/']))->not->toHaveKey('home');
});
