<?php

namespace VmEngine\Synapse;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use VmEngine\Synapse\Console\Commands\SynAppsConfig;
use VmEngine\Synapse\Console\Commands\SynAppsDiscover;
use VmEngine\Synapse\Console\Commands\SynAppsLinkErrors;
use VmEngine\Synapse\Console\Commands\SynAppsPatchResources;
use VmEngine\Synapse\Console\Commands\SynAppsPatchTests;
use VmEngine\Synapse\Console\Commands\SynAppsPublishSettings;
use VmEngine\Synapse\Console\Commands\SynAppsSetup;
use VmEngine\Synapse\Console\Commands\SynapseDbConfig;
use VmEngine\Synapse\Console\Commands\SynapseDbLang;
use VmEngine\Synapse\Services\DbConfig;
use VmEngine\Synapse\Services\DbLang;
use VmEngine\Synapse\Services\SynapseUtils;
use VmEngine\Synapse\Services\SyNav;

/**
 * @SuppressWarnings PHPMD.StaticAccess
 */
class SynapseServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../stubs/config/synapse.php' => config_path('synapse.php'),
                __DIR__.'/../stubs/config/synapps.php' => config_path('synapps.php'),
            ], 'synapse-config');

            $this->publishes([
                synapps_path('resources/assets') => public_path('assets'),
            ], 'synapse-assets');

            $this->commands([
                SynAppsSetup::class,
                SynAppsDiscover::class,
                SynAppsPatchResources::class,
                SynAppsPatchTests::class,
                SynAppsLinkErrors::class,
                SynAppsConfig::class,
                SynAppsPublishSettings::class,
                SynapseDbConfig::class,
                SynapseDbLang::class,
            ]);
        }

        if (
            SynapseUtils::isSynAppsAutoloaded() &&
            $this->app->runningInConsole()
        ) {
            $this->bootDbOverride();
        }

        Schema::defaultStringLength(191);
        bcscale(8);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('synav', function ($app) {
            return new SyNav;
        });
        $this->app->singleton('dbconf', function ($app) {
            return new DbConfig;
        });
        $this->app->singleton('dblang', function ($app) {
            return new DbLang;
        });
        if (SynapseUtils::isSynAppsAutoloaded()) {
            $this->bindAlias();
            $this->bindPublicPath();
            $this->bindInterface();
            $this->bindClass();
            $this->registerSynAppsServiceProviders();
            $this->registerLangFiles();
            $this->overrideConfigs();
        }
    }

    private function bindAlias()
    {
        if (config('synapps.binding.alias')) {
            $this->app->booting(function () {
                $loader = \Illuminate\Foundation\AliasLoader::getInstance();
                $classList = config('synapps.binding.alias', []);
                foreach ($classList as $classAliasName => $className) {
                    $loader->alias($classAliasName, $className);
                }
            });
        }
    }

    private function bindPublicPath()
    {
        if (config('synapps.system.public_path')) {
            $this->app->bind('path.public', function () {
                return realpath(base_path(config('synapps.system.public_path')));
            });
        }
    }

    private function bindInterface()
    {
        foreach (config('synapse.binding.interface', []) as $contract => $service) {
            $this->app->bind(
                $contract,
                $service
            );
        }
    }

    private function bindClass()
    {
        foreach (config('synapps.binding.class', []) as $class => $newClass) {
            $this->app->bind($class, function ($app, $args) use ($newClass) {
                if (empty($args)) {
                    return new $newClass;
                }

                return new $newClass(...$args);
            });
        }
    }

    private function registerSynAppsServiceProviders()
    {
        $moduleConfigs = config('synapps.modules', []);

        foreach ($moduleConfigs as $moduleConfig) {
            if ($moduleConfig['is_package']) {
                continue;
            }
            // VmEngine\Example\ExampleServiceProvider
            $serviceProviderClass = 'SynApps\\Modules\\'
                .$moduleConfig['id_case']['pascal'].'\\'
                .$moduleConfig['id_case']['pascal'].'ServiceProvider';

            if (class_exists($serviceProviderClass)) {
                $this->app->register($serviceProviderClass);
            }
        }
    }

    private function bootDbOverride()
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(config('synapse.migration_path.general', []));
            // @phpstan-ignore-next-line DatabaseSeeder exists in the consuming Laravel application
            $this->callAfterResolving(DatabaseSeeder::class, function ($seeder) {
                $seedList = config('synapse.seed_path.general');
                foreach ($seedList as $seederFile) {
                    if (! file_exists($seederFile)) {
                        continue;
                    }
                    $seederClassList = include $seederFile;
                    if (count($seederClassList)) {
                        $seeder->call($seederClassList);
                    }
                }
            });
        }
    }

    private function registerLangFiles()
    {
        $langPath = config('synapse.lang_path.general', []);
        $that = $this;
        array_walk(
            $langPath,
            function ($v) use ($that) {
                $that->loadTranslationsFrom($v[0], $v[1]);
            }
        );
    }

    private function overrideConfigs()
    {
        $path = synapps_path('config'.DS.'override');
        if (! file_exists($path)) {
            return;
        }

        $configs = glob($path.DS.'*.php');
        foreach ($configs as $config) {
            $configName = basename($config, '.php');
            if (file_exists(base_path('config'.DS.$configName.'.php'))) {
                $this->mergeConfigFrom($config, $configName);
            }
        }
    }
}
