<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Services;

use Closure;
use Illuminate\Support\Str;

defined('DS') or define('DS', DIRECTORY_SEPARATOR);

/**
 * @SuppressWarnings PHPMD.StaticAccess
 */
class SynapseUtils
{
    /**
     * Generate Module List by given namespaces
     *
     * @param  array<string, mixed>  $namespaces  format NAMESPACE => [path_to_module_group, FILTER PREFIX]
     * @return array<int, mixed>
     */
    public static function listModuleNamespaces(array $namespaces, Closure $func): array
    {
        return self::readModules($namespaces, function ($namespace, $namespaceValue, $moduleName, $modulePath, $func) {
            if ($namespace != 'SynModule') {
                $moduleName = Str::snake($moduleName);
            }

            return $func($namespace, $modulePath, $moduleName, $modulePath, $namespaceValue);
        }, $func);
    }

    /**
     * @param  array<string, mixed>  $namespaces
     * @return array<int, mixed>
     */
    public static function listModuleResources(array $namespaces, string $path, ?Closure $func = null): array
    {
        return self::readModules(
            $namespaces,
            function ($namespace, $namespaceValue, $moduleName, $modulePath, $func) use ($path) {
                $resourcePath = $modulePath.DS.$path;

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
        $path = base_path('composer.json');
        if (! file_exists($path)) {
            return false;
        }

        $composerJson = file_get_contents($path);

        return $composerJson !== false && str_contains($composerJson, 'SynApps');
    }

    /**
     * @param  array<string, mixed>  $namespaces
     * @return array<int, mixed>
     */
    private static function readModules(
        array $namespaces,
        Closure $process,
        Closure $final
    ): array {
        $return = [];

        // $namespace = namespace
        // $namespaceValue[0] = path ke namespace
        // $namespaceValue[1] = array prefix directory yang ada di path bersangkutan, false jika tanpa prefix
        foreach ($namespaces as $namespace => $namespaceValue) {
            $tmp = [];
            if ($namespaceValue[1]) {
                if (! is_array($namespaceValue[1])) {
                    $namespaceValue[1] = [$namespaceValue[1]];
                }
                foreach ($namespaceValue[1] as $prefix) {
                    $paths = glob($namespaceValue[0].DS.$prefix.'*'.DS) ?: [];

                    $tmp = array_merge(
                        $tmp,
                        $paths
                    );
                }
            } else {
                $tmp = glob($namespaceValue[0].DS.'*', GLOB_ONLYDIR) ?: [];
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

    public static function copyDirectory(string $source, string $destination): bool
    {
        // Normalize paths to remove trailing slashes.
        $source = rtrim($source, '/\\');
        $destination = rtrim($destination, '/\\');

        // Create the destination directory if it doesn't exist.
        if (! is_dir($destination)) {
            if (! mkdir($destination, 0777, true)) {
                error_log("Failed to create destination directory: $destination");

                return false;
            }
        }

        // Open the source directory.
        $dir = opendir($source);
        if ($dir === false) {
            error_log("Failed to open source directory: $source");

            return false;
        }

        // Loop through the contents of the source directory.
        while (false !== ($file = readdir($dir))) {
            // Skip '.' and '..' entries.
            if (($file != '.') && ($file != '..')) {
                $sourcePath = $source.DIRECTORY_SEPARATOR.$file;
                $destinationPath = $destination.DIRECTORY_SEPARATOR.$file;

                if (is_dir($sourcePath)) {
                    // If it's a directory, recursively call the function.
                    if (! self::copyDirectory($sourcePath, $destinationPath)) {
                        closedir($dir);

                        return false;
                    }
                } else {
                    // If it's a file, copy it.
                    if (! copy($sourcePath, $destinationPath)) {
                        error_log("Failed to copy file from $sourcePath to $destinationPath");
                        closedir($dir);

                        return false;
                    }
                }
            }
        }

        closedir($dir);

        return true;
    }
}
