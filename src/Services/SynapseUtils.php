<?php

namespace VmEngine\Synapse\Services;

use Closure;
use Illuminate\Support\Str;

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
            function ($namespace, $namespaceValue, $moduleName, $modulePath, $func) use ($path, $func) {
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
            $tmp = glob(sprintf('%s*', $namespaceValue[0]), GLOB_ONLYDIR);
            foreach ($tmp as $modulePath) {
                // $moduleName : nama/folder module nya
                $moduleName = substr($modulePath, strrpos($modulePath, DS) + 1);
                // cek jika ada prefix maka hanya ambil path yg sesuai prefix nya saja
                if ($namespaceValue[1]) {
                    if (! is_array($namespaceValue[1])) {
                        $namespaceValue[1] = [$namespaceValue[1]];
                    }
                    $skipModule = true;
                    foreach ($namespaceValue[1] as $val) {
                        if (strpos($moduleName, $val) === 0) {
                            $moduleName = str_replace($namespaceValue[1], '', $moduleName);
                            $skipModule = false;
                        }
                    }
                    if ($skipModule) {
                        continue;
                    }
                }

                $tempReturn = $process($namespace, $namespaceValue, $moduleName, $modulePath, $final);
                if ($tempReturn) {
                    $return[] = $tempReturn;
                }
            }
        }

        return $return;
    }
}
