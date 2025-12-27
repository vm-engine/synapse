<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Services\ModuleGenerator;

use Illuminate\Support\Str;

class NamespaceResolver
{
    /**
     * Resolve namespace for directory-based modules
     * Pattern: SynApps\Modules\{ModuleName}\{ComponentType}
     */
    public function resolveDirectoryNamespace(string $moduleName, string $componentType = ''): string
    {
        $baseNamespace = 'SynApps\\Modules\\'.Str::studly($moduleName);

        if (empty($componentType)) {
            return $baseNamespace;
        }

        return $baseNamespace.'\\'.$componentType;
    }

    /**
     * Resolve namespace for package-based modules
     * Pattern: {Vendor}\{ModuleName}\{ComponentType}
     */
    public function resolvePackageNamespace(
        string $moduleName,
        string $componentType = '',
        string $vendor = 'VmEngine'
    ): string {
        $baseNamespace = $vendor.'\\'.Str::studly($moduleName);

        if (empty($componentType)) {
            return $baseNamespace;
        }

        return $baseNamespace.'\\'.$componentType;
    }

    /**
     * Resolve factory namespace
     * Pattern: SynDB\Modules\{ModuleName}\Factories (directory)
     * Pattern: {Vendor}\{ModuleName}\Factories (package)
     */
    public function resolveFactoryNamespace(
        string $moduleName,
        bool $isPackage = false,
        string $vendor = 'VmEngine'
    ): string {
        if ($isPackage) {
            return $this->resolvePackageNamespace($moduleName, 'Factories', $vendor);
        }

        return 'SynDB\\Modules\\'.Str::studly($moduleName).'\\Factories';
    }

    /**
     * Resolve seeder namespace
     * Pattern: SynDB\Modules\{ModuleName}\Seeders (directory)
     * Pattern: {Vendor}\{ModuleName}\Seeders (package)
     */
    public function resolveSeederNamespace(
        string $moduleName,
        bool $isPackage = false,
        string $vendor = 'VmEngine'
    ): string {
        if ($isPackage) {
            return $this->resolvePackageNamespace($moduleName, 'Seeders', $vendor);
        }

        return 'SynDB\\Modules\\'.Str::studly($moduleName).'\\Seeders';
    }

    /**
     * Get component subdirectory based on type
     * Maps component types to their subdirectories
     */
    public function getComponentSubdirectory(string $type): string
    {
        $map = [
            'model' => 'src/Models',
            'controller' => 'src/Http/Controllers',
            'livewire' => 'src/Livewire',
            'request' => 'src/Http/Requests',
            'middleware' => 'src/Http/Middleware',
            'event' => 'src/Events',
            'listener' => 'src/Listeners',
            'job' => 'src/Jobs',
            'provider' => 'src/Providers',
            'policy' => 'src/Policies',
            'notification' => 'src/Notifications',
            'mail' => 'src/Mail',
            'factory' => 'database/factories',
            'seeder' => 'database/seeders',
            'migration' => 'database/migrations',
            'test' => 'tests/Feature',
        ];

        return $map[strtolower($type)] ?? '';
    }

    /**
     * Get component namespace segment based on type
     * Maps component types to their namespace segments
     */
    public function getComponentNamespace(string $type): string
    {
        $map = [
            'model' => 'Models',
            'controller' => 'Http\\Controllers',
            'livewire' => 'Livewire',
            'request' => 'Http\\Requests',
            'middleware' => 'Http\\Middleware',
            'event' => 'Events',
            'listener' => 'Listeners',
            'job' => 'Jobs',
            'provider' => 'Providers',
            'policy' => 'Policies',
            'notification' => 'Notifications',
            'mail' => 'Mail',
        ];

        return $map[strtolower($type)] ?? '';
    }

    /**
     * Convert module name to proper case formats
     */
    public function generateIdCases(string $name): array
    {
        return [
            'snake' => Str::snake($name),
            'pascal' => Str::studly($name),
            'slug' => Str::slug($name),
            'studly' => Str::camel($name),
        ];
    }

    /**
     * Generate class name from component name and type
     * Ensures proper suffixing (e.g., PostController, CreatePostRequest)
     */
    public function generateClassName(string $name, string $type): string
    {
        $name = Str::studly($name);

        // Check if name already has the suffix
        $suffixes = [
            'controller' => 'Controller',
            'request' => 'Request',
            'middleware' => 'Middleware',
            'event' => 'Event',
            'listener' => 'Listener',
            'job' => 'Job',
            'provider' => 'ServiceProvider',
            'policy' => 'Policy',
            'notification' => 'Notification',
            'mail' => 'Mail',
            'factory' => 'Factory',
            'seeder' => 'Seeder',
        ];

        $suffix = $suffixes[strtolower($type)] ?? '';

        if (empty($suffix)) {
            return $name;
        }

        // If name already ends with the suffix, return as-is
        if (Str::endsWith($name, $suffix)) {
            return $name;
        }

        return $name.$suffix;
    }

    /**
     * Generate table name from model name
     */
    public function generateTableName(string $modelName): string
    {
        return Str::snake(Str::pluralStudly($modelName));
    }

    /**
     * Extract vendor name from composer package name
     * e.g., "vm-engine/synmod-blog" => "VmEngine"
     */
    public function extractVendorFromPackageName(string $packageName): string
    {
        $parts = explode('/', $packageName);
        $vendorSlug = $parts[0];

        return Str::studly($vendorSlug);
    }

    /**
     * Extract module name from composer package name
     * e.g., "vm-engine/synmod-blog" => "Blog"
     */
    public function extractModuleFromPackageName(string $packageName): string
    {
        $parts = explode('/', $packageName);
        $moduleSlug = $parts[1] ?? 'Module';

        // Remove common prefixes
        $moduleSlug = preg_replace('/^(synmod-|synmodule-|synapps-)/', '', $moduleSlug);

        return Str::studly($moduleSlug);
    }
}
