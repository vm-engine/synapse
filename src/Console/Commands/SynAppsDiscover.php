<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Console\Commands;

use Composer\Semver\Comparator;
use Illuminate\Console\Command;

class SynAppsDiscover extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'synapps:discover';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Discover modules and dependencies, update composer.json/package.json autoload entries';

    /** @var array<string, mixed> */
    protected array $packageJson = [];

    /** @var array<string, mixed> */
    protected array $composerJson = [];

    /** @var array<string, string> */
    protected array $namespaces = [];

    protected int $npmDeps = 0;

    protected int $composerDeps = 0;

    protected int $npmDevDeps = 0;

    protected int $composerDevDeps = 0;

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->packageJson = read_json_file(base_path('package.json'));
        $this->composerJson = read_json_file(base_path('composer.json'));

        $this->discoverSynapseModules();

        $this->discoverModuleDeps();

        $this->discoverSynAppsDeps();

        $this->informDiscoveries();

        $this->updateComposer();

        $this->updateNpm();

        $this->call('synapps:config');

        if ($this->npmDeps || $this->npmDevDeps) {
            $this->components->info('Compiling assets');
            exec('npm run build');
        }
    }

    private function discoverSynapseModules(): void
    {
        $moduleConfigPaths = glob(synapps_path('modules'.DS.'*'.DS.'module.json')) ?: [];

        foreach ($moduleConfigPaths as $moduleConfigPath) {
            $moduleConfig = read_json_file($moduleConfigPath);
            $modulePath = ltrim(str_replace(
                base_path(),
                '',
                dirname($moduleConfigPath)
            ));
            $moduleNamespace = 'SynApps\\Modules\\'.$moduleConfig['id_case']['pascal'].'\\';
            $dbNamespace = 'SynDB\\Modules\\'.$moduleConfig['id_case']['pascal'].'\\';
            $this->namespaces[$moduleNamespace] = ltrim($modulePath, DS).DS.'src'.DS;
            $this->namespaces[$dbNamespace.'Seeders\\'] = ltrim($modulePath, DS).DS.'database'.DS.'seeders'.DS;
            $this->namespaces[$dbNamespace.'Factories\\'] = ltrim($modulePath, DS).DS.'database'.DS.'factories'.DS;
            $this->components->info("Discovered Module: {$moduleConfig['name']} at {$modulePath}");

            $this->compareDependencies($moduleConfig['dependencies']);
        }
    }

    private function discoverModuleDeps(): void
    {
        $modulePackages = array_merge(
            glob(base_path('vendor/*/synmod-*/module.json')) ?: [],
            glob(base_path('vendor/*/synmodule-*/module.json')) ?: [],
            glob(base_path('vendor/*/synapps-*/module.json')) ?: [],
        );
        foreach ($modulePackages as $packageConfigPath) {
            $moduleConfig = read_json_file($packageConfigPath);

            $this->compareDependencies($moduleConfig['dependencies']);
        }
    }

    private function discoverSynAppsDeps(): void
    {
        $appsConfig = read_json_file(synapps_path('config/apps.json'));
        $this->compareDependencies($appsConfig['dependencies']);

        if (! file_exists(synapps_path('config/apps.dev.json'))) {
            file_put_contents(synapps_path('config/apps.dev.json'), '{}');
        }
        $appsDevConfig = read_json_file(synapps_path('config/apps.dev.json'));
        if (isset($appsDevConfig['dependencies'])) {
            $this->compareDependencies($appsDevConfig['dependencies']);
        }
    }

    private function informDiscoveries(): void
    {
        $this->components->info('Added '.count($this->namespaces).' Namespaces');
        if ($this->npmDeps) {
            $this->components->info('Discovered '.$this->npmDeps.' NPM Dependencies');
        }
        if ($this->composerDeps) {
            $this->components->info('Discovered '.$this->composerDeps.' Composer Dependencies');
        }
        if ($this->npmDeps) {
            $this->components->info('Discovered '.$this->npmDeps.' NPM Dev Dependencies');
        }
        if ($this->composerDeps) {
            $this->components->info('Discovered '.$this->composerDeps.' Composer Dev Dependencies');
        }
    }

    private function updateComposer(): void
    {
        // Clean up deleted module namespaces
        $existingAutoload = $this->composerJson['autoload']['psr-4'] ?? [];
        $cleanedAutoload = [];
        $removedCount = 0;

        foreach ($existingAutoload as $namespace => $path) {
            // Check if this is a SynApps or SynDB module namespace
            if (
                preg_match('/^SynApps\\\\Modules\\\\/', $namespace) ||
                preg_match('/^SynDB\\\\Modules\\\\/', $namespace)
            ) {
                // Check if this namespace is still in our discovered namespaces
                if (! isset($this->namespaces[$namespace])) {
                    // Module was deleted, skip this namespace
                    $this->components->warn("Removing deleted module namespace: {$namespace}");
                    $removedCount++;

                    continue;
                }
            }

            // Keep this namespace
            $cleanedAutoload[$namespace] = $path;
        }

        // Merge discovered namespaces with cleaned autoload
        $this->composerJson['autoload']['psr-4'] = array_merge(
            $cleanedAutoload,
            $this->namespaces
        );

        if ($removedCount > 0) {
            $this->components->info("Removed {$removedCount} namespace(s) from deleted modules");
        }

        $this->components->info('Updating composer.json');
        file_put_contents(
            base_path('composer.json'),
            json_encode($this->composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        if ($this->composerDeps || $this->composerDevDeps || $removedCount > 0) {
            $this->components->info('Running composer update');
            exec('composer update');

            return;
        }
        $this->components->info('Running composer dump-autoload');
        exec('composer dump-autoload');
    }

    private function updateNpm(): void
    {
        if (empty($this->packageJson['dependencies'])) {
            $this->packageJson['dependencies'] = new \stdClass;
        }
        if (empty($this->packageJson['devDependencies'])) {
            $this->packageJson['devDependencies'] = new \stdClass;
        }

        if ($this->npmDeps || $this->npmDevDeps) {
            $this->components->info('Updating package.json');
            file_put_contents(
                base_path('package.json'),
                json_encode($this->packageJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );
            $this->components->info('Running npm install');
            exec('npm i');
        }
    }

    /** @param  array<string, mixed>  $moduleDependecies */
    private function compareDependencies(array $moduleDependecies): void
    {
        $this->compareDeps('packageJson', 'dependencies', $moduleDependecies['npm']['main'] ?? [], 'npmDeps');
        $this->compareDeps('packageJson', 'devDependencies', $moduleDependecies['npm']['dev'] ?? [], 'npmDevDeps');
        $this->compareDeps('composerJson', 'require', $moduleDependecies['composer']['main'] ?? [], 'composerDeps');
        $this->compareDeps('composerJson', 'require-dev', $moduleDependecies['composer']['dev'] ?? [], 'composerDevDeps');
    }

    /** @param  array<string, mixed>  $toLoop */
    private function compareDeps(
        string $thisVar,
        string $thisVarKey,
        array $toLoop,
        string $counterVar
    ): void {
        $collection = $this->{$thisVar}[$thisVarKey] ?? [];

        foreach ($toLoop as $package => $version) {
            if (
                ! isset($collection[$package]) ||
                Comparator::greaterThan($version, $collection[$package])
            ) {
                $collection[$package] = $version;
                $this->{$counterVar}++;
                $this->components->info('Discovered Dependency: '.$package.' - '.$version);
            }
        }

        $this->{$thisVar}[$thisVarKey] = $collection;
    }
}
