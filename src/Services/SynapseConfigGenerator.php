<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Services;

use Illuminate\Support\Str;

defined('DS') or define('DS', DIRECTORY_SEPARATOR);

/** @SuppressWarnings PHPMD.StaticAccess */

/**
 * Class for generating config arrays, by reading the module paths for specific patterns
 * or module location in synapps folder
 */
class SynapseConfigGenerator
{
    /**
     * Default Empty Configuration
     *
     * @var array<string, mixed>
     */
    public static array $defaultConfig = [
        'bindings' => [
            'controller' => [],
            'interface' => [],
            'route' => [],
        ],
        'lang_path' => [],  // language path
        'module_path' => [],  // controller path
        'view_path' => [],  // blade view path
        'migration_path' => [],  // migrations path
        'seed_path' => [],  // seeds path
        'namespaces' => [],
    ];

    /**
     * Generate The Configuration
     *
     * @return array<string, mixed> Generated Config Arrays
     */
    public static function generate(): array
    {
        // If the compiled configuration Json file is not exists, return the default empty configurations
        if (! self::hasCompiledConfigFile()) {
            return self::$defaultConfig;
        }

        // If the compiled file exists but invalid, also return the default empty configurations
        $apps = self::loadCompiledConfigFile('apps');

        if (! $apps) {
            return self::$defaultConfig;
        }

        $config = self::$defaultConfig;

        self::setGeneralNamespaces($config);
        self::setModuleNamespaces($config);
        self::setResourcesPaths($config);
        self::setDatabasePaths($config);

        file_put_contents(
            synapps_path('config/compiled/synapse.json'),
            json_encode($config, JSON_PRETTY_PRINT)
        );

        return $config;
    }

    /** @return array<string, mixed> */
    public static function config(): array
    {
        if (! file_exists(synapps_path('config/compiled/synapse.json'))) {
            return self::generate();
        }

        return read_json_file(synapps_path('config/compiled/synapse.json'));
    }

    /**
     * Check if compiled Json configuration file already exists
     * If the folder does not exists, it will be created
     */
    public static function hasCompiledConfigFile(): bool
    {
        if (! file_exists(base_path('synapps/config/compiled'))) {
            mkdir(base_path('synapps/config/compiled'), 0775, true);
        }

        return file_exists(base_path('synapps/config/compiled/apps.json'));
    }

    /**
     * Load the compiled configuration Json file
     *
     * @return array<string, mixed>|null
     */
    private static function loadCompiledConfigFile(string $file = 'client'): ?array
    {
        $filePath = base_path('synapps/config/compiled/'.$file.'.json');
        if (file_exists($filePath)) {
            return read_json_file($filePath);
        }

        return null;
    }

    /**
     * Set Base Array of paths to look up
     *
     * @param  array<string, mixed>  $config  Config Pointer
     */
    private static function setGeneralNamespaces(array &$config): void
    {
        $config['namespaces']['general']['SynApps\Modules'] = [
            synapps_path('modules'),
            false,  // no prefix
        ];
        $config['namespaces']['general']['SynModule'] = [
            base_path('vendor'.DS.'*'),
            // prefix with synmod-, synmodule- or synapps-
            ['synmod-', 'synmodule-', 'synapps-'],
        ];
    }

    /** @param  array<string, mixed>  $config */
    private static function setModuleNamespaces(array &$config): void
    {
        $modulePaths = ['general' => []];
        $tmpModulePaths = SynapseUtils::listModuleNamespaces(
            $config['namespaces']['general'],
            function ($namespace, $path, $moduleName) {
                return [$moduleName, $path];
            }
        );

        foreach ($tmpModulePaths as $val) {
            $modulePaths['general'][$val[0]] = $val[1];
        }

        $config['module_path'] = $modulePaths;
    }

    /** @param  array<string, mixed>  $config */
    private static function setResourcesPaths(array &$config): void
    {
        $langPaths = SynapseUtils::listModuleResources(
            $config['namespaces']['general'],
            'lang',
            function ($namespace, $path, $moduleName, $modulePath, $namespaceValue) {
                return [$path, Str::slug($moduleName, '-')];
            }
        );
        $langPaths[] = [synapps_path('lang'), 'synapps'];
        $langPaths[] = [realpath(__DIR__.DS.'..'.DS.'..'.DS.'lang'), 'synapse'];

        $viewPaths = SynapseUtils::listModuleResources(
            $config['namespaces']['general'],
            'resources'.DS.'views',
            function ($namespace, $path, $moduleName, $modulePath, $namespaceValue) {
                return [$path, Str::slug($moduleName, '-')];
            }
        );
        $viewPaths[] = [synapps_path('resources'.DS.'views'), 'synapps'];
        $viewPaths[] = [
            realpath(__DIR__.DS.'..'.DS.'..'.DS.'resources'.DS.'views'),
            'synapse',
        ];

        $config['lang_path'] = ['general' => $langPaths];
        $config['view_path'] = ['general' => $viewPaths];
    }

    /** @param  array<string, mixed>  $config */
    private static function setDatabasePaths(array &$config): void
    {
        $migrationPaths = SynapseUtils::listModuleResources(
            $config['namespaces']['general'],
            'database'.DS.'migrations'
        );

        $centralMigrationPaths = [];
        if (file_exists(synapps_path('config/tenancy.json'))) {
            $centralMigrationPaths = SynapseUtils::listModuleResources(
                $config['namespaces']['general'],
                'database'.DS.'migrations'.DS.'central'
            );
        }
        $migrationPaths = array_merge($migrationPaths, $centralMigrationPaths);

        $seedPaths = SynapseUtils::listModuleResources(
            $config['namespaces']['general'],
            'database'.DS.'seeders',
            function ($namespace, $resourcePath, $moduleName, $modulePath, $namespaceValue) {
                return $resourcePath.DS.'seeders.php';
            }
        );

        $migrationPaths[] = realpath(__DIR__.DS.'..'.DS.'..'.DS.'database'.DS.'migrations');

        $config['migration_path'] = ['general' => $migrationPaths];
        $config['seed_path'] = ['general' => $seedPaths];
    }
}
