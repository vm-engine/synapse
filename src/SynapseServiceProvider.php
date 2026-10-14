<?php

declare(strict_types=1);

namespace VmEngine\Synapse;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Foundation\Bootstrap\SetRequestForConsole;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use VmEngine\Synapse\Console\Commands\Generators\MakeCastCommand;
use VmEngine\Synapse\Console\Commands\Generators\MakeChannelCommand;
use VmEngine\Synapse\Console\Commands\Generators\MakeClassCommand;
use VmEngine\Synapse\Console\Commands\Generators\MakeCommandCommand;
use VmEngine\Synapse\Console\Commands\Generators\MakeComponentCommand;
use VmEngine\Synapse\Console\Commands\Generators\MakeConfigCommand;
use VmEngine\Synapse\Console\Commands\Generators\MakeControllerCommand;
use VmEngine\Synapse\Console\Commands\Generators\MakeDataCommand;
use VmEngine\Synapse\Console\Commands\Generators\MakeEnumCommand;
use VmEngine\Synapse\Console\Commands\Generators\MakeEventCommand;
use VmEngine\Synapse\Console\Commands\Generators\MakeExceptionCommand;
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
use VmEngine\Synapse\Console\Commands\SynAppsCopyStubs;
use VmEngine\Synapse\Console\Commands\SynAppsCopyThemes;
use VmEngine\Synapse\Console\Commands\SynAppsDiscover;
use VmEngine\Synapse\Console\Commands\SynAppsLinkErrors;
use VmEngine\Synapse\Console\Commands\SynAppsMake;
use VmEngine\Synapse\Console\Commands\SynAppsMakeTheme;
use VmEngine\Synapse\Console\Commands\SynAppsPatchResources;
use VmEngine\Synapse\Console\Commands\SynAppsPublishSettings;
use VmEngine\Synapse\Console\Commands\SynAppsReconfig;
use VmEngine\Synapse\Console\Commands\SynAppsRemoveModule;
use VmEngine\Synapse\Console\Commands\SynAppsSearchableForget;
use VmEngine\Synapse\Console\Commands\SynAppsSearchableSetup;
use VmEngine\Synapse\Console\Commands\SynAppsSetup;
use VmEngine\Synapse\Console\Commands\SynAppsUpgradeAudit;
use VmEngine\Synapse\Console\Commands\SynAppsVendorFonts;
use VmEngine\Synapse\Console\Commands\SynapseDbConfig;
use VmEngine\Synapse\Console\Commands\SynapseDbLang;
use VmEngine\Synapse\Console\Commands\SynapseExcelCleanup;
use VmEngine\Synapse\Console\Commands\SynapsePatchComposer;
use VmEngine\Synapse\Console\Commands\SynapseRestoreComposer;
use VmEngine\Synapse\Console\Commands\SynotifSetup;
use VmEngine\Synapse\Finder\SynapseFinder;
use VmEngine\Synapse\Http\Middlewares\IpFilter;
use VmEngine\Synapse\Http\Middlewares\LogApiRequest;
use VmEngine\Synapse\Http\Middlewares\NoCacheHeaders;
use VmEngine\Synapse\Http\Middlewares\SecureHeaders;
use VmEngine\Synapse\Nav\NavBadgeRepository;
use VmEngine\Synapse\Services\DbConfig;
use VmEngine\Synapse\Services\DbLang;
use VmEngine\Synapse\Services\SynapseUtils;
use VmEngine\Synapse\Services\SyNav;
use VmEngine\Synapse\Services\SynSearchable;

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
        // Menu badges: listen for what each declared badge says makes its count stale. After boot,
        // so every module's menu config is in place.
        $this->app->booted(fn () => $this->app->make(NavBadgeRepository::class)->registerListeners());

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
                SynotifSetup::class,
                SynAppsSearchableSetup::class,
                SynAppsSearchableForget::class,
                SynAppsDiscover::class,
                SynAppsCopyResources::class,
                SynAppsPatchResources::class,
                SynAppsLinkErrors::class,
                SynAppsConfig::class,
                SynAppsReconfig::class,
                SynAppsRemoveModule::class,
                SynAppsPublishSettings::class,
                SynAppsCopyStubs::class,
                SynAppsCopyThemes::class,
                SynAppsMakeTheme::class,
                SynAppsVendorFonts::class,
                SynapseDbConfig::class,
                SynapseDbLang::class,
                SynapsePatchComposer::class,
                SynapseRestoreComposer::class,
                SynapseExcelCleanup::class,
                SynAppsUpgradeAudit::class,
                SynAppsMake::class,
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
                MakeClassCommand::class,
                MakeExceptionCommand::class,
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

            // Register spatie/laravel-data command only when package is installed
            if (class_exists('Spatie\\LaravelData\\Data')) {
                $this->commands([MakeDataCommand::class]);
            }
        }

        // Load package migrations (configs, languages, excel tasks)
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if (
            SynapseUtils::isSynAppsAutoloaded() &&
            $this->app->runningInConsole() &&
            $this->isSynAppsConfigured()
        ) {
            $this->bootDbOverride();
        }

        // Abort if APP_DEBUG is enabled in production to prevent information leakage
        if ($this->app->environment('production') && config('app.debug')) {
            abort(500, 'APP_DEBUG must be disabled in production.');
        }

        // Register SecureHeaders middleware globally (Laravel 12 compatible)
        $this->app->booted(function () {
            $kernel = $this->app->make(Kernel::class);
            $kernel->prependMiddleware(IpFilter::class);
            $kernel->pushMiddleware(SecureHeaders::class);
        });

        // Register the api.log middleware alias — attached manually per
        // route group, never applied globally.
        $this->app->make(Router::class)->aliasMiddleware('api.log', LogApiRequest::class);

        // Register the no-cache middleware alias for authenticated backend routes
        $this->app->make(Router::class)->aliasMiddleware('no-cache', NoCacheHeaders::class);

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
        // Indonesian for framework strings Synapse pages and mail print (notification mail
        // subcopy and footer, layout footers); a project's own lang/id.json still wins.
        $this->loadJsonTranslationsFrom(__DIR__.'/../lang');

        $this->app->singleton('synav', function ($app) {
            return new SyNav;
        });
        // Scoped, not singleton: its per-request memo must not outlive a request on long-running servers.
        $this->app->scoped(NavBadgeRepository::class);
        $this->app->singleton('synsearchable', function ($app) {
            return new SynSearchable;
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
        if (SynapseUtils::isSynAppsAutoloaded() && $this->isSynAppsConfigured()) {
            $this->registerSynapseFinder();
            $this->bindAlias();
            $this->bindInterface();
            $this->bindClass();
            $this->registerSynAppsServiceProviders();
            $this->registerLangFiles();
            $this->registerConfigs();
            $this->overrideConfigs();
            $this->overrideAppUrl();
            $this->overrideLivewireCsp();
        }
    }

    private function isSynAppsConfigured(): bool
    {
        return file_exists(synapps_path('config/compiled/synapps.json'));
    }

    private function registerSynapseFinder(): void
    {
        $this->app->singleton('livewire.finder', function () {
            $finder = new SynapseFinder;
            $finder->addLocation(classNamespace: config('livewire.class_namespace'));

            return $finder;
        });
    }

    private function bindAlias(): void
    {
        if (config('synapps.binding.alias')) {
            $this->app->booting(function () {
                $loader = AliasLoader::getInstance();
                $classList = config('synapps.binding.alias', []);
                foreach ($classList as $classAliasName => $className) {
                    $loader->alias($classAliasName, $className);
                }
            });
        }
    }

    private function bindInterface(): void
    {
        foreach (config('synapse.binding.interface', []) as $contract => $service) {
            $this->app->bind(
                $contract,
                $service
            );
        }
    }

    private function bindClass(): void
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

    private function registerSynAppsServiceProviders(): void
    {
        $moduleConfigs = config('synapps.modules', []);

        foreach ($moduleConfigs as $moduleConfig) {
            if (isset($moduleConfig['is_package']) && $moduleConfig['is_package']) {
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

    private function bootDbOverride(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(config('synapse.migration_path.general', []));
            $this->callAfterResolving('Database\Seeders\DatabaseSeeder', function ($seeder) {
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

    private function registerLangFiles(): void
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

    private function registerConfigs(): void
    {
        $modules = config('synapps.modules', []);
        foreach ($modules as $module) {
            if (! $module['is_package']) {
                $configPath = synapps_path('modules'.DS.$module['id'].DS.'config');
                if (file_exists($configPath)) {
                    $configs = glob($configPath.DS.'*.php') ?: [];
                    foreach ($configs as $config) {
                        $configName = basename($config, '.php');
                        $this->mergeConfigFrom($config, $configName);
                    }
                }
            }
        }
    }

    private function overrideConfigs(): void
    {
        $path = synapps_path('config'.DS.'override');
        if (! file_exists($path)) {
            return;
        }

        $configs = glob($path.DS.'*.php') ?: [];
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
     * Override Laravel's default app.url with the domain configured in
     * synapps.apps.urls.domain, picking dev/prod based on the current environment.
     */
    private function overrideAppUrl(): void
    {
        $isProduction = $this->app->environment('production');
        $domain = config('synapps.apps.urls.domain.'.($isProduction ? 'prod' : 'dev'));
        $originalAppUrl = config('app.url');

        config([
            'app.url' => empty($domain)
                ? $originalAppUrl
                : ($isProduction ? 'https://' : 'http://').$domain,
        ]);

        // A console process (queue worker, scheduler, artisan) built its request from the .env
        // APP_URL before providers registered, so route()/url() — e.g. links in queued mail —
        // would ignore the override. Rebuild that request from the overridden app.url.
        if ($this->app->runningInConsole()) {
            (new SetRequestForConsole)->bootstrap($this->app);
        }
    }

    /**
     * Force Livewire's CSP-safe mode on for every synapse-based project.
     *
     * Livewire 4's `csp_safe` config flag swaps Alpine's evaluator to its
     * CSP-safe build (no `new Function()`/`eval()`), which is required for
     * `SecureHeaders::buildContentSecurityPolicy()` to drop `'unsafe-eval'`.
     * Set unconditionally here (not via `overrideConfigs()`'s
     * synapps/config/override/*.php mechanism) because that mechanism only
     * applies when the target config file is already published — this must
     * be active even on a fresh synapse project that never ran
     * `vendor:publish --tag=livewire-config`.
     */
    private function overrideLivewireCsp(): void
    {
        config(['livewire.csp_safe' => true]);
    }

    /**
     * Deep merge configuration arrays
     *
     * @param  array<string, mixed>  $base
     * @param  array<string, mixed>  $override
     * @return array<string, mixed>
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
