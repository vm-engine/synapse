<?php

declare(strict_types=1);

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use VmEngine\Synapse\Console\Commands\SynAppsConfig;

/**
 * Write the root composer.json that synapps:discover reads and rewrites.
 *
 * @param  array<string, string>  $require
 * @param  array<string, string>  $psr4
 */
function writeRootComposerJson(array $require = [], array $psr4 = ['App\\' => 'app/']): void
{
    file_put_contents(base_path('composer.json'), json_encode([
        'name' => 'synapse/discover-test',
        'require' => $require,
        'autoload' => ['psr-4' => $psr4],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

/**
 * Read the root composer.json back after the command rewrote it.
 *
 * @return array<string, mixed>
 */
function readRootComposerJson(): array
{
    $decoded = json_decode((string) file_get_contents(base_path('composer.json')), true);

    return is_array($decoded) ? $decoded : [];
}

/**
 * Write a discoverable synapps module declaring the given dependencies.
 *
 * Every id is prefixed "discover-test-" so afterEach can clean them up.
 *
 * @param  array<string, string>  $composerDeps
 * @param  array<string, string>  $npmDeps
 * @param  array<string, string>  $composerDevDeps
 */
function writeDiscoverableModule(
    string $id,
    array $composerDeps = [],
    array $npmDeps = [],
    array $composerDevDeps = [],
): string {
    $path = base_path('synapps/modules/discover-test-'.$id);

    File::ensureDirectoryExists($path);

    file_put_contents($path.'/module.json', json_encode([
        'id' => 'discover_test_'.str_replace('-', '_', $id),
        'id_case' => ['pascal' => 'DiscoverTest'.str_replace(' ', '', ucwords(str_replace('-', ' ', $id)))],
        'name' => 'Discover Test '.$id,
        'dependencies' => [
            'composer' => ['main' => $composerDeps, 'dev' => $composerDevDeps],
            'npm' => ['main' => $npmDeps],
        ],
    ]));

    return $path;
}

beforeEach(function () {
    Process::fake();

    // A private base path: synapps:discover rewrites base_path('composer.json'), which
    // ComposerPatchCommandsTest also owns in the shared Testbench skeleton, and test files
    // run side by side under --parallel.
    $this->originalBasePath = base_path();
    $this->sandbox = sys_get_temp_dir().'/synapse-discover-'.bin2hex(random_bytes(6));
    File::ensureDirectoryExists($this->sandbox.'/synapps/config');
    File::ensureDirectoryExists($this->sandbox.'/synapps/modules');
    app()->setBasePath($this->sandbox);

    writeRootComposerJson();
    file_put_contents(base_path('package.json'), json_encode(['dependencies' => new stdClass]));
    file_put_contents(synapps_path('config/apps.json'), json_encode(['dependencies' => []]));

    // The real synapps:config runs both config generators against a bare
    // Testbench app; a no-op stand-in keeps these tests about the composer step.
    $this->app->bind(SynAppsConfig::class, fn () => new class extends Command
    {
        protected $signature = 'synapps:config';

        protected $description = 'Fake synapps:config for tests';

        public function handle(): int
        {
            $this->components->info('fake synapps:config ran');

            return self::SUCCESS;
        }
    });
});

afterEach(function () {
    app()->setBasePath($this->originalBasePath);
    File::deleteDirectory($this->sandbox);
});

test('a new module dependency triggers a targeted composer update, never a bare one', function () {
    writeDiscoverableModule('targeted', ['vendor/new-package' => '^1.0']);

    $this->artisan('synapps:discover')->assertSuccessful();

    Process::assertRan(['composer', 'update', 'vendor/new-package', '--with-all-dependencies']);
    Process::assertNotRan(['composer', 'update']);

    expect(readRootComposerJson()['require'])->toBe(['vendor/new-package' => '^1.0']);
});

test('a deleted module namespace only dumps the autoloader', function () {
    writeRootComposerJson([], [
        'App\\' => 'app/',
        'SynApps\\Modules\\Gone\\' => 'synapps/modules/gone/src/',
    ]);

    $this->artisan('synapps:discover')->assertSuccessful();

    expect(readRootComposerJson()['autoload']['psr-4'])
        ->not->toHaveKey('SynApps\\Modules\\Gone\\')
        ->toHaveKey('App\\');

    Process::assertRan(['composer', 'dump-autoload']);
    Process::assertNotRan(
        fn ($process) => is_array($process->command) && ($process->command[1] ?? null) === 'update'
    );
});

test('a higher constraint wins even when the root constraint is a compound one', function () {
    writeRootComposerJson(['vendor/thing' => '>=1.0']);
    writeDiscoverableModule('compound', ['vendor/thing' => '^2.0']);

    $this->artisan('synapps:discover')->assertSuccessful();

    expect(readRootComposerJson()['require']['vendor/thing'])->toBe('^2.0');
    Process::assertRan(['composer', 'update', 'vendor/thing', '--with-all-dependencies']);
});

test('a lower module constraint leaves the root constraint alone', function () {
    writeRootComposerJson(['vendor/thing' => '^3.0']);
    writeDiscoverableModule('lower', ['vendor/thing' => '^2.0']);

    $this->artisan('synapps:discover')->assertSuccessful();

    expect(readRootComposerJson()['require']['vendor/thing'])->toBe('^3.0');
    Process::assertRan(['composer', 'dump-autoload']);
    Process::assertNotRan(
        fn ($process) => is_array($process->command) && ($process->command[1] ?? null) === 'update'
    );
});

test('a module branch constraint never overwrites a stable root constraint', function () {
    writeRootComposerJson(['vendor/thing' => '^1.0']);
    writeDiscoverableModule('devbranch', ['vendor/thing' => 'dev-main']);

    $this->artisan('synapps:discover')
        ->expectsOutputToContain('leaving the root constraint alone')
        ->assertSuccessful();

    expect(readRootComposerJson()['require']['vendor/thing'])->toBe('^1.0');
    Process::assertRan(['composer', 'dump-autoload']);
});

test('a failing composer update is reported, does not stop the run, and fails the command', function () {
    Process::fake(['*' => Process::result(
        output: 'Your requirements could not be resolved to an installable set of packages.',
        exitCode: 1,
    )]);

    writeDiscoverableModule('failing', ['vendor/new-package' => '^1.0']);

    $this->artisan('synapps:discover')
        ->expectsOutputToContain('composer update vendor/new-package --with-all-dependencies')
        ->expectsOutputToContain('fake synapps:config ran')
        ->expectsOutputToContain('composer install --no-scripts')
        ->assertFailed();
});

test('npm dependencies are installed and built through the reporting runner', function () {
    writeDiscoverableModule('npm', [], ['some-npm-package' => '^1.0']);

    $this->artisan('synapps:discover')->assertSuccessful();

    Process::assertRan(['npm', 'install']);
    Process::assertRan(['npm', 'run', 'build']);
});

test('dev dependency counts are reported from the dev counters', function () {
    writeDiscoverableModule('devcount', ['vendor/main-package' => '^1.0'], [], ['vendor/dev-package' => '^2.0']);

    $this->artisan('synapps:discover')
        ->expectsOutputToContain('Discovered 1 Composer Dependencies')
        ->expectsOutputToContain('Discovered 1 Composer Dev Dependencies')
        ->assertSuccessful();

    $composer = readRootComposerJson();

    expect($composer['require'])->toBe(['vendor/main-package' => '^1.0'])
        ->and($composer['require-dev'])->toBe(['vendor/dev-package' => '^2.0']);
});

test('module autoload paths are written with forward slashes and replace Windows-written ones', function () {
    writeRootComposerJson([], [
        'App\\' => 'app/',
        'SynApps\\Modules\\DiscoverTestSlashes\\' => 'synapps\\modules\\discover-test-slashes\\src\\',
    ]);
    writeDiscoverableModule('slashes');

    $this->artisan('synapps:discover')->assertSuccessful();

    expect(readRootComposerJson()['autoload']['psr-4'])
        ->toMatchArray([
            'SynApps\\Modules\\DiscoverTestSlashes\\' => 'synapps/modules/discover-test-slashes/src/',
            'SynDB\\Modules\\DiscoverTestSlashes\\Seeders\\' => 'synapps/modules/discover-test-slashes/database/seeders/',
            'SynDB\\Modules\\DiscoverTestSlashes\\Factories\\' => 'synapps/modules/discover-test-slashes/database/factories/',
        ]);
});
