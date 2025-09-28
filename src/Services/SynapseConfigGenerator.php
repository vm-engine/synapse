<?php

namespace VmEngine\Synapse\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

defined('DS') or define('DS', DIRECTORY_SEPARATOR);

/** @SuppressWarnings(PHPMD.StaticAccess) */

/**
 * Class for generating config arrays, by reading the module paths for specific patterns
 * or module location in synapps folder
 */
class SynapseConfigGenerator
{
    /**
     * Default Empty Configuration
     */
    public static $defaultConfig = [
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
     * @return array Generated Config Arrays
     */
    public static function generate()
    {
        // If the compiled configuration Json file is not exists, return the default empty configurations
        if (!self::hasCompiledConfigFile()) {
            return self::$defaultConfig;
        }

        // If the compiled file exists but invalid, also return the default empty configurations
        $system = self::loadCompiledConfigFile('system');
        $apps = self::loadCompiledConfigFile('apps');

        if (!$system || !$apps) {
            return self::$defaultConfig;
        }

        $config = self::$defaultConfig;

        self::setGeneralNamespaces($config);
        self::setModuleNamespaces($config);
        self::setResourcesPaths($config);
        self::setDatabasePaths($config);

        return $config;
    }

    /**
     * Check if compiled Json configuration file already exists
     * If the folder does not exists, it will be created
     *
     * @return boolean
     */
    public static function hasCompiledConfigFile()
    {
        if (!file_exists(base_path('synapps/config/compiled'))) {
            mkdir(base_path('synapps/config/compiled'), 0775, true);
        }
        return file_exists(base_path('synapps/config/compiled/apps.json')) &&
            file_exists(base_path('synapps/config/compiled/system.json'));
    }

    /**
     * Load the compiled configuration Json file
     *
     * @param string $file
     * @return array|null
     */
    private static function loadCompiledConfigFile($file = 'client')
    {
        $filePath = base_path('synapps/config/compiled/' . $file . '.json');
        if (file_exists($filePath)) {
            $content = file_get_contents($filePath);

            return json_decode($content, true);
        }

        return null;
    }

    /**
     * Set Base Array of paths to look up
     *
     * @param array $config Config Pointer
     * @return void
     */
    private static function setGeneralNamespaces(&$config)
    {
        $config['namespaces']['general']['SynApps\Modules'] = [
            synapps_path('modules'),
            false,  // no prefix
        ];
        $config['namespaces']['general']['SynModule'] = [
            base_path('vendor' . DS . '*'),
            // prefix with synmod-, synmodule- or synapp-
            ['synmod-', 'synmodule-', 'synapp-'],
        ];
    }

    private static function setModuleNamespaces(&$config)
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

        // Log::debug('tmp module path', $tmpModulePaths);
        // Log::debug('module path', $modulePaths);
        $config['module_path'] = $modulePaths;
    }

    private static function setResourcesPaths(&$config)
    {
        $langPaths = SynapseUtils::listModuleResources(
            $config['namespaces']['general'],
            'lang',
            function ($namespace, $path, $moduleName, $modulePath, $namespaceValue) {
                return [$path, Str::slug($moduleName, '-')];
            }
        );
        $langPaths[] = synapps_path('lang', 'synapps');

        $viewPaths = SynapseUtils::listModuleResources(
            $config['namespaces']['general'],
            'resources' . DS . 'views',
            function ($namespace, $path, $moduleName, $modulePath, $namespaceValue) {
                return [$path, Str::slug($moduleName, '-')];
            }
        );
        $viewPaths[] = [synapps_path('resources' . DS . 'views'), 'synapps'];
        $viewPaths[] = [
            realpath(__DIR__ . DS . '..' . DS . '..' . DS . 'resources' . DS . 'views'),
            'synapse'
        ];

        $config['lang_path'] = ['general' => $langPaths];
        $config['view_path'] = ['general' => $viewPaths];
    }

    private static function setDatabasePaths(&$config)
    {
        $migrationPaths = SynapseUtils::listModuleResources(
            $config['namespaces']['general'],
            'database' . DS . 'migrations'
        );
        $seedPaths = SynapseUtils::listModuleResources(
            $config['namespaces']['general'],
            'database' . DS . 'seeders',
            function ($namespace, $resourcePath, $moduleName, $modulePath, $namespaceValue) {
                return $resourcePath . DS . 'seeders.php';
            }
        );

        $config['migration_path'] = ['general' => $migrationPaths];
        $config['seed_path'] = ['general' => $seedPaths];
    }
}
