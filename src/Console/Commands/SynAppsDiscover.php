<?php

namespace VmEngine\Synapse\Console\Commands;

use Composer\Semver\Comparator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 * @SuppressWarnings(PHPMD.ElseStatement)
 */
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
    protected $description = 'Discover Modules in synapps folder';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $packageJson = json_decode(file_get_contents(base_path('package.json')), true);
        $composerJson = json_decode(file_get_contents(base_path('composer.json')), true);

        $moduleConfigPaths = glob(synapps_path('modules' . DS . '*' . DS . 'module.json'));

        $namespaces = [];
        $npmDependencies = 0;
        $composerDependencies = 0;
        $npmDevDependencies = 0;
        $composerDevDependencies = 0;
        foreach ($moduleConfigPaths as $moduleConfigPath) {
            $moduleConfig = json_decode(file_get_contents($moduleConfigPath), true);
            $modulePath = ltrim(str_replace(
                base_path(),
                "",
                dirname($moduleConfigPath)
            ));
            $moduleNamespace = "SynApps\\Modules\\" . $moduleConfig['id_case']['pascal'] . "\\";
            $dbNamespace = "SynDB\\Modules\\" . $moduleConfig['id_case']['pascal'] . "\\";
            $namespaces[$moduleNamespace] = ltrim($modulePath, DS) . DS . "src" . DS;
            $namespaces[$dbNamespace . "Seeders\\"] = ltrim($modulePath, DS) . DS . "database" . DS . "seeders" . DS;
            $namespaces[$dbNamespace . "Factories\\"] = ltrim($modulePath, DS) . DS . "database" . DS . "factories" . DS;
            $this->info("Discovered Module: {$moduleConfig['name']} at {$modulePath}");

            $moduleDependecies = $this->compareDependencies($composerJson, $packageJson, $moduleConfig['dependencies']);
            $npmDependencies += $moduleDependecies['npmDependencies'];
            $composerDependencies += $moduleDependecies['composerDependencies'];
            $npmDevDependencies += $moduleDependecies['npmDevDependencies'];
            $composerDevDependencies += $moduleDependecies['composerDevDependencies'];
        }

        $modulePackages = array_merge(
            glob(base_path('vendor/*/synmod-*/module.json')),
            glob(base_path('vendor/*/synmodule-*/module.json')),
            glob(base_path('vendor/*/synapps-*/module.json')),
        );
        foreach ($modulePackages as $packageConfigPath) {
            $moduleConfig = json_decode(file_get_contents($packageConfigPath), true);

            $packageDependecies = $this->compareDependencies($composerJson, $packageJson, $moduleConfig['dependencies']);
            $npmDependencies += $packageDependecies['npmDependencies'];
            $composerDependencies += $packageDependecies['composerDependencies'];
            $npmDevDependencies += $packageDependecies['npmDevDependencies'];
            $composerDevDependencies += $packageDependecies['composerDevDependencies'];
        }

        $appsConfig = json_decode(file_get_contents(synapps_path('config/apps.json')), true);
        $appsDependecies = $this->compareDependencies($composerJson, $packageJson, $appsConfig['dependencies']);
        $npmDependencies += $appsDependecies['npmDependencies'];
        $composerDependencies += $appsDependecies['composerDependencies'];
        $npmDevDependencies += $appsDependecies['npmDevDependencies'];
        $composerDevDependencies += $appsDependecies['composerDevDependencies'];

        $appsDevConfig = json_decode(file_get_contents(synapps_path('config/apps.dev.json')), true);
        if (isset($appsDevConfig['dependencies'])) {
            $appsDevDependecies = $this->compareDependencies($composerJson, $packageJson, $appsDevConfig['dependencies']);
            $npmDependencies += $appsDevDependecies['npmDependencies'];
            $composerDependencies += $appsDevDependecies['composerDependencies'];
            $npmDevDependencies += $appsDevDependecies['npmDevDependencies'];
            $composerDevDependencies += $appsDevDependecies['composerDevDependencies'];
        }

        $this->info('Added ' . count($namespaces) . ' Namespaces');
        if ($npmDependencies) {
            $this->info('Discovered ' . $npmDependencies . ' NPM Dependencies');
        }
        if ($composerDependencies) {
            $this->info('Discovered ' . $composerDependencies . ' Composer Dependencies');
        }
        if ($npmDependencies) {
            $this->info('Discovered ' . $npmDependencies . ' NPM Dev Dependencies');
        }
        if ($composerDependencies) {
            $this->info('Discovered ' . $composerDependencies . ' Composer Dev Dependencies');
        }

        $composerJson['autoload']['psr-4'] = array_merge(
            $composerJson['autoload']['psr-4'],
            $namespaces
        );

        $this->info('Updating composer.json');
        file_put_contents(
            base_path('composer.json'),
            json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        if (empty($packageJson['dependencies'])) {
            $packageJson['dependencies'] = new \stdClass();
        }
        if (empty($packageJson['devDependencies'])) {
            $packageJson['devDependencies'] = new \stdClass();
        }

        if ($composerDependencies || $composerDevDependencies) {
            $this->info('Running composer update');
            exec('composer update');
        } else {
            $this->info('Running composer dump-autoload');
            exec('composer dump-autoload');
        }

        if ($npmDependencies || $npmDevDependencies) {
            $this->info('Updating package.json');
            file_put_contents(
                base_path('package.json'),
                json_encode($packageJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );
            $this->info('Running npm install');
            exec('npm i');
        }

        $this->info('Caching Config');
        Artisan::call('config:cache');

        if ($npmDependencies || $npmDevDependencies) {
            $this->info('Compiling assets');
            exec('npm run build');
        }
    }

    private function compareDependencies(&$composerJson, &$packageJson, $moduleDependecies)
    {
        $npmDependenciesCollection = $packageJson['dependencies'] ?? [];
        $composerDependenciesCollection = $composerJson['require'] ?? [];
        $npmDevDependenciesCollection = $packageJson['devDependencies'] ?? [];
        $composerDevDependenciesCollection = $composerJson['require-dev'] ?? [];

        $npmDependencies = 0;
        $composerDependencies = 0;
        $npmDevDependencies = 0;
        $composerDevDependencies = 0;
        foreach ($moduleDependecies['npm']['main'] as $npmPackage => $packageVersion) {
            if (
                !isset($npmDependenciesCollection[$npmPackage])
                || Comparator::greaterThan($packageVersion, $npmDependenciesCollection[$npmPackage])
            ) {
                $npmDependenciesCollection[$npmPackage] = $packageVersion;
                $npmDependencies++;
                $this->info('Discovered NPM Dependency: ' . $npmPackage . ' - ' . $packageVersion);
            }
        }

        foreach ($moduleDependecies['composer']['main'] as $composerPackage => $packageVersion) {
            if (
                !isset($composerDependenciesCollection[$composerPackage])
                || Comparator::greaterThan($packageVersion, $composerDependenciesCollection[$composerPackage])
            ) {
                $composerDependenciesCollection[$composerPackage] = $packageVersion;
                $composerDependencies++;
                $this->info('Discovered Composer Dependency: ' . $composerPackage . ' - ' . $packageVersion);
            }
        }

        foreach ($moduleDependecies['npm']['dev'] as $npmPackage => $packageVersion) {
            if (
                !isset($npmDevDependenciesCollection[$npmPackage])
                || Comparator::greaterThan($packageVersion, $npmDevDependenciesCollection[$npmPackage])
            ) {
                $npmDevDependenciesCollection[$npmPackage] = $packageVersion;
                $npmDevDependencies++;
                $this->info('Discovered NPM Dev Dependency: ' . $npmPackage . ' - ' . $packageVersion);
            }
        }

        foreach ($moduleDependecies['composer']['dev'] as $composerPackage => $packageVersion) {
            if (
                !isset($composerDevDependenciesCollection[$composerPackage])
                || Comparator::greaterThan($packageVersion, $composerDevDependenciesCollection[$composerPackage])
            ) {
                $composerDevDependenciesCollection[$composerPackage] = $packageVersion;
                $composerDevDependencies++;
                $this->info('Discovered Composer Dev Dependency: ' . $composerPackage . ' - ' . $packageVersion);
            }
        }

        $composerJson['require'] = $composerDependenciesCollection;
        $composerJson['require-dev'] = $composerDevDependenciesCollection;

        $packageJson['dependencies'] = $npmDependenciesCollection;
        $packageJson['devDependencies'] = $npmDevDependenciesCollection;

        return [
            'npmDependencies' => $npmDependencies,
            'composerDependencies' => $composerDependencies,
            'npmDevDependencies' => $npmDevDependencies,
            'composerDevDependencies' => $composerDevDependencies,
        ];
    }
}
