<?php

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

    protected $packageJson = [];

    protected $composerJson = [];

    protected $namespaces = [];

    protected $npmDeps = 0;

    protected $composerDeps = 0;

    protected $npmDevDeps = 0;

    protected $composerDevDeps = 0;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->packageJson = json_decode(file_get_contents(base_path('package.json')), true);
        $this->composerJson = json_decode(file_get_contents(base_path('composer.json')), true);

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

    private function discoverSynapseModules()
    {
        $moduleConfigPaths = glob(synapps_path('modules'.DS.'*'.DS.'module.json'));

        foreach ($moduleConfigPaths as $moduleConfigPath) {
            $moduleConfig = json_decode(file_get_contents($moduleConfigPath), true);
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

    private function discoverModuleDeps()
    {
        $modulePackages = array_merge(
            glob(base_path('vendor/*/synmod-*/module.json')),
            glob(base_path('vendor/*/synmodule-*/module.json')),
            glob(base_path('vendor/*/synapps-*/module.json')),
        );
        foreach ($modulePackages as $packageConfigPath) {
            $moduleConfig = json_decode(file_get_contents($packageConfigPath), true);

            $this->compareDependencies($moduleConfig['dependencies']);
        }
    }

    private function discoverSynAppsDeps()
    {
        $appsConfig = json_decode(file_get_contents(synapps_path('config/apps.json')), true);
        $this->compareDependencies($appsConfig['dependencies']);

        if (! file_exists(synapps_path('config/apps.dev.json'))) {
            file_put_contents(synapps_path('config/apps.dev.json'), '{}');
        }
        $appsDevConfig = json_decode(file_get_contents(synapps_path('config/apps.dev.json')), true);
        if (isset($appsDevConfig['dependencies'])) {
            $this->compareDependencies($appsDevConfig['dependencies']);
        }
    }

    private function informDiscoveries()
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

    private function updateComposer()
    {
        $this->composerJson['autoload']['psr-4'] = array_merge(
            $this->composerJson['autoload']['psr-4'],
            $this->namespaces
        );

        $this->components->info('Updating composer.json');
        file_put_contents(
            base_path('composer.json'),
            json_encode($this->composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        if ($this->composerDeps || $this->composerDevDeps) {
            $this->components->info('Running composer update');
            exec('composer update');

            return;
        }
        $this->components->info('Running composer dump-autoload');
        exec('composer dump-autoload');
    }

    private function updateNpm()
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

    private function compareDependencies($moduleDependecies)
    {
        $this->compareDeps('packageJson', 'dependencies', $moduleDependecies['npm']['main'], 'npmDeps');
        $this->compareDeps('packageJson', 'devDependencies', $moduleDependecies['npm']['dev'], 'npmDevDeps');
        $this->compareDeps('composerJson', 'require', $moduleDependecies['composer']['main'], 'composerDeps');
        $this->compareDeps('composerJson', 'require-dev', $moduleDependecies['composer']['dev'], 'composerDevDeps');

        return [
            'npmDeps' => $this->npmDeps,
            'composerDeps' => $this->composerDeps,
            'npmDevDeps' => $this->npmDevDeps,
            'composerDevDeps' => $this->composerDevDeps,
        ];
    }

    private function compareDeps(
        $thisVar,
        $thisVarKey,
        $toLoop,
        $counterVar
    ) {
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
