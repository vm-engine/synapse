<?php

namespace VmEngine\Synapse;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use VmEngine\Synapse\Console\Commands\Generators\MakeCastCommand;
use VmEngine\Synapse\Console\Commands\Generators\MakeChannelCommand;
use VmEngine\Synapse\Console\Commands\Generators\MakeCommandCommand;
use VmEngine\Synapse\Console\Commands\Generators\MakeComponentCommand;
use VmEngine\Synapse\Console\Commands\Generators\MakeConfigCommand;
use VmEngine\Synapse\Console\Commands\Generators\MakeControllerCommand;
use VmEngine\Synapse\Console\Commands\Generators\MakeEnumCommand;
use VmEngine\Synapse\Console\Commands\Generators\MakeEventCommand;
use VmEngine\Synapse\Console\Commands\Generators\MakeFactoryCommand;
use VmEngine\Synapse\Console\Commands\Generators\MakeInterfaceCommand;
use VmEngine\Synapse\Console\Commands\Generators\MakeJobCommand;
use VmEngine\Synapse\Console\Commands\Generators\MakeJobMiddlewareCommand;
use VmEngine\Synapse\Console\Commands\Generators\MakeListenerCommand;
use VmEngine\Synapse\Console\Commands\Generators\MakeLivewireCommand;
use VmEngine\Synapse\Console\Commands\Generators\MakeMailCommand;
use VmEngine\Synapse\Console\Commands\Generators\MakeMiddlewareCommand;
use VmEngine\Synapse\Console\Commands\Generators\MakeMigrationCommand;
use VmEngine\Synapse\Console\Commands\Generators\MakeModelCommand;
use VmEngine\Synapse\Console\Commands\Generators\MakeModuleCommand;
use VmEngine\Synapse\Console\Commands\Generators\MakeNotificationCommand;
use VmEngine\Synapse\Console\Commands\Generators\MakeObserverCommand;
use VmEngine\Synapse\Console\Commands\Generators\MakePolicyCommand;
use VmEngine\Synapse\Console\Commands\Generators\MakeProviderCommand;
use VmEngine\Synapse\Console\Commands\Generators\MakeRequestCommand;
use VmEngine\Synapse\Console\Commands\Generators\MakeResourceCommand;
use VmEngine\Synapse\Console\Commands\Generators\MakeRuleCommand;
use VmEngine\Synapse\Console\Commands\Generators\MakeScopeCommand;
use VmEngine\Synapse\Console\Commands\Generators\MakeSeederCommand;
use VmEngine\Synapse\Console\Commands\Generators\MakeTestCommand;
use VmEngine\Synapse\Console\Commands\Generators\MakeTraitCommand;
use VmEngine\Synapse\Console\Commands\Generators\MakeViewCommand;
use VmEngine\Synapse\Console\Commands\Generators\SynmodMakeCommand;
use VmEngine\Synapse\Console\Commands\SynAppsConfig;
use VmEngine\Synapse\Console\Commands\SynAppsCopyResources;
use VmEngine\Synapse\Console\Commands\SynAppsDiscover;
use VmEngine\Synapse\Console\Commands\SynAppsLinkErrors;
use VmEngine\Synapse\Console\Commands\SynAppsMakeTheme;
use VmEngine\Synapse\Console\Commands\SynAppsPatchResources;
use VmEngine\Synapse\Console\Commands\SynAppsPatchTests;
use VmEngine\Synapse\Console\Commands\SynAppsPublishSettings;
use VmEngine\Synapse\Console\Commands\SynAppsReconfig;
use VmEngine\Synapse\Console\Commands\SynAppsSetup;
use VmEngine\Synapse\Console\Commands\SynapseDbConfig;
use VmEngine\Synapse\Console\Commands\SynapseDbLang;
use VmEngine\Synapse\Console\Commands\SynapseExcelCleanup;
use VmEngine\Synapse\Console\Commands\SynapsePatchComposer;
use VmEngine\Synapse\Console\Commands\SynapseRestoreComposer;
use VmEngine\Synapse\Http\Middlewares\SecureHeaders;
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

            $this->publishes([
                __DIR__.'/../stubs/themes/default' => base_path('synapps/resources/themes/default'),
            ], 'synapse-theme');

            $this->commands([
                SynAppsSetup::class,
                SynAppsDiscover::class,
                SynAppsCopyResources::class,
                SynAppsPatchResources::class,
                SynAppsPatchTests::class,
                SynAppsLinkErrors::class,
                SynAppsConfig::class,
                SynAppsReconfig::class,
                SynAppsPublishSettings::class,
                SynAppsMakeTheme::class,
                SynapseDbConfig::class,
                SynapseDbLang::class,
                SynapsePatchComposer::class,
                SynapseRestoreComposer::class,
                SynapseExcelCleanup::class,
                // Generator Commands
                MakeModuleCommand::class,
                MakeModelCommand::class,
                MakeControllerCommand::class,
                MakeMigrationCommand::class,
                MakeFactoryCommand::class,
                MakeSeederCommand::class,
                MakeLivewireCommand::class,
                MakeRequestCommand::class,
                MakeMiddlewareCommand::class,
                MakeEventCommand::class,
                MakeListenerCommand::class,
                MakeJobCommand::class,
                MakeProviderCommand::class,
                MakePolicyCommand::class,
                MakeNotificationCommand::class,
                MakeMailCommand::class,
                MakeTestCommand::class,
                // New Generator Commands
                MakeCastCommand::class,
                MakeChannelCommand::class,
                MakeCommandCommand::class,
                MakeComponentCommand::class,
                MakeConfigCommand::class,
                MakeEnumCommand::class,
                MakeInterfaceCommand::class,
                MakeJobMiddlewareCommand::class,
                MakeObserverCommand::class,
                MakeResourceCommand::class,
                MakeRuleCommand::class,
                MakeScopeCommand::class,
                MakeTraitCommand::class,
                MakeViewCommand::class,
                // Unified Make Command
                SynmodMakeCommand::class,
            ]);
        }

        // Load package migrations (configs, languages, excel tasks)
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if (
            SynapseUtils::isSynAppsAutoloaded() &&
            $this->app->runningInConsole()
        ) {
            $this->bootDbOverride();
        }

        // Register SecureHeaders middleware globally (Laravel 12 compatible)
        $this->app->booted(function () {
            $kernel = $this->app->make(Kernel::class);
            $kernel->pushMiddleware(SecureHeaders::class);
        });

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
        $this->app->singleton('synapse.theme', function ($app) {
            return new Services\ThemeService;
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
                $overrideConfig = require $config;
                $currentConfig = config($configName, []);

                // Deep merge arrays
                $mergedConfig = $this->deepMergeConfig($currentConfig, $overrideConfig);

                // Set the merged config back
                config()->set($configName, $mergedConfig);

                // If app.timezone was overridden, update PHP's timezone setting
                if ($configName === 'app' && isset($overrideConfig['timezone'])) {
                    date_default_timezone_set(config('app.timezone'));
                }
            }
        }
    }

    /**
     * Deep merge configuration arrays
     */
    private function deepMergeConfig(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (isset($base[$key]) && is_array($base[$key]) && is_array($value)) {
                $base[$key] = $this->deepMergeConfig($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }

        return $base;
    }
}
