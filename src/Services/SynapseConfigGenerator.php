<?php

namespace VmEngine\Synapse\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

defined('DS') or define('DS', DIRECTORY_SEPARATOR);

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class SynapseConfigGenerator
{
    public static $defaultConfig = [
        'bindings' => [
            'controller' => [],
            'interface' => [],
            'route' => [],
        ],
        'lang_path' => [], // language path
        'module_path' => [], // controller path
        'view_path' => [], // blade view path
        'migration_path' => [], // migrations path
        'seed_path' => [], // seeds path
        /*
                * namespace ke path lokasi daftar module module
                *  NAMESPACE => [path_to_module_group, FILTER PREFIX
                */
        'namespaces' => [],

        'view_path' => [],
    ];

    public static function generate()
    {
        if (! self::hasCompiledConfigFile()) {
            return self::$defaultConfig;
        }

        $system = self::loadCompiledConfigFile('system');
        $apps= self::loadCompiledConfigFile('apps');

        if (! $system || ! $apps) {
            return self::$defaultConfig;
        }

        $config = self::$defaultConfig;

        self::setGeneralNamespaces($config);
        self::setModuleNamespaces($config);
        self::setResourcesPaths($config);
        self::setDatabasePaths($config);

        return $config;
    }

    private static function hasCompiledConfigFile()
    {
        if (!file_exists(base_path('synapps/config/compiled'))) {
            mkdir(base_path('synapps/config/compiled'), 0775, true);
        }
        return file_exists(base_path('synapps/config/compiled/apps.json'))
            && file_exists(base_path('synapps/config/compiled/system.json'));
    }

    private static function loadCompiledConfigFile($file = 'client')
    {
        $filePath = base_path('synapps/config/compiled/' . $file . '.json');
        if (file_exists($filePath)) {
            $content = file_get_contents($filePath);

            return json_decode($content, true);
        }

        return null;
    }

    private static function setGeneralNamespaces(&$config)
    {
        $config['namespaces']['general']['SynApps\\Modules'] = [
            synapps_path('modules'),
            false, // no prefix
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
                $unprefixedComponent = str_replace($namespaceValue[1], '', $moduleName);

                return [$path, strtolower($unprefixedComponent)];
            }
        );
        $langPaths[] = synapps_path('lang');

        $viewPaths = SynapseUtils::listModuleResources(
            $config['namespaces']['general'],
            'resources' . DS . 'views',
            function ($namespace, $path, $moduleName, $modulePath, $namespaceValue) {
                return [$path, Str::slug($moduleName, '-')];
            }
        );
        $viewPaths[] = synapps_path('resources/views');

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
