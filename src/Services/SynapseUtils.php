<?php

namespace VmEngine\Synapse\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Closure;

defined('DS') or define('DS', DIRECTORY_SEPARATOR);

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class SynapseUtils
{
    /**
     * Generate Module List by given namespaces
     *
     * @param  array  $namespaces,  format NAMESPACE => [path_to_module_group, FILTER PREFIX]
     * @param  Closure  $func
     */
    public static function listModuleNamespaces(array $namespaces, $func): array
    {
        return self::readModules($namespaces, function ($namespace, $namespaceValue, $moduleName, $modulePath, $func) {
            if ($namespace != 'SynModule') {
                $moduleName = Str::snake($moduleName);
            }

            return $func($namespace, $modulePath, $moduleName, $modulePath, $namespaceValue);
        }, $func);
    }

    public static function listModuleResources($namespaces, $path, $func = null)
    {
        return self::readModules(
            $namespaces,
            function ($namespace, $namespaceValue, $moduleName, $modulePath, $func) use ($path) {
                $resourcePath = $modulePath . DS . $path;

                if (is_dir($resourcePath)) {
                    return $func($namespace, $resourcePath, $moduleName, $modulePath, $namespaceValue);
                }

                return null;
            },
            $func ?? function ($namespace, $path) {
                return $path;
            }
        );
    }

    public static function isSynAppsAutoloaded(): bool
    {
        $composerJson = file_get_contents(base_path('composer.json'));
        return strpos($composerJson, 'SynApps') !== false;
    }

    private static function readModules(
        array $namespaces,
        Closure $process,
        Closure $final
    ) {
        $return = [];

        // $namespace = namespace
        // $namespaceValue[0] = path ke namespace
        // $namespaceValue[1] = array prefix directory yang ada di path bersangkutan, false jika tanpa prefix
        foreach ($namespaces as $namespace => $namespaceValue) {
            $tmp = [];
            if ($namespaceValue[1]) {
                if (!is_array($namespaceValue[1])) {
                    $namespaceValue[1] = [$namespaceValue[1]];
                }
                foreach ($namespaceValue[1] as $prefix) {
                    $paths = glob($namespaceValue[0] . DS . $prefix . '*' . DS);

                    $tmp = array_merge(
                        $tmp,
                        $paths
                    );
                }
            } else {
                $tmp = glob($namespaceValue[0] . DS . '*', GLOB_ONLYDIR);
            }
            foreach ($tmp as $modulePath) {
                $moduleName = last(explode(DS, trim($modulePath, DS)));
                if ($namespaceValue[1]) {
                    $moduleName = str_replace($namespaceValue[1], '', $moduleName);
                }
                $moduleName = Str::snake($moduleName);

                $tempReturn = $process($namespace, $namespaceValue, $moduleName, $modulePath, $final);
                if ($tempReturn) {
                    $return[] = $tempReturn;
                }
            }
        }

        return $return;
    }
}
