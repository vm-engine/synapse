<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Traits;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use ReflectionClass;

/**
 * Auto-register Livewire and Blade components for Synapse modules
 *
 * This trait automatically discovers and registers:
 * - Livewire MFC/SFC components from resources/views/components/ directory (Livewire v4)
 * - Livewire class-based components from src/Livewire/ directory
 * - Class-based Blade components from src/View/Components/ directory
 *
 * Uses Livewire v4's namespace system (addNamespace) for automatic component discovery,
 * supporting nested directories natively.
 *
 * Usage in module ServiceProvider:
 * ```php
 * use VmEngine\Synapse\Traits\AutoRegistersComponents;
 *
 * class ExampleServiceProvider extends ServiceProvider
 * {
 *     use AutoRegistersComponents;
 *
 *     public function register(): void
 *     {
 *         $this->registerComponents();
 *     }
 * }
 * ```
 *
 * Components are registered with namespace notation:
 * - <livewire:example::example-form /> (class-based from src/Livewire/)
 * - <livewire:example::todo-form /> (MFC from resources/views/components/)
 * - <livewire:example::forms.contact-form /> (nested MFC)
 */
trait AutoRegistersComponents
{
    /**
     * The component prefix (auto-detected from namespace if not set)
     */
    protected ?string $componentPrefix = null;

    /**
     * Whether to auto-register Livewire components
     */
    protected bool $autoRegisterLivewireComponents = true;

    /**
     * Whether to auto-register class-based Blade components
     */
    protected bool $autoRegisterBladeComponents = true;

    /**
     * Register all module components (Livewire + Blade)
     *
     * Call this in your ServiceProvider's register() method:
     * ```php
     * public function register(): void
     * {
     *     $this->registerComponents();
     * }
     * ```
     */
    protected function registerComponents(): void
    {
        if ($this->autoRegisterLivewireComponents) {
            $this->registerLivewireNamespace();
        }

        if ($this->autoRegisterBladeComponents) {
            $this->registerBladeComponents();
        }
    }

    /**
     * Register a Livewire namespace for automatic component discovery
     *
     * Uses Livewire v4's addNamespace() to register both class-based and MFC/SFC
     * components in a single call. Livewire handles nested directory discovery natively.
     *
     * Registered paths:
     * - viewPath: resources/views/components/ (MFC/SFC components)
     * - classNamespace: {ModuleNamespace}\Livewire (class-based components)
     * - classPath: src/Livewire/ (class file location)
     * - classViewPath: resources/views/livewire/ (class component views)
     *
     * Result: <livewire:prefix::component-name />
     */
    protected function registerLivewireNamespace(): void
    {
        $reflection = new ReflectionClass($this);
        $namespace = $reflection->getNamespaceName();
        $basePath = dirname($reflection->getFileName());
        $moduleRoot = dirname($basePath);

        $prefix = $this->getComponentPrefix();
        $livewireNamespace = $namespace.'\\Livewire';
        $livewirePath = $basePath.'/Livewire';
        $componentsPath = $moduleRoot.'/resources/views/components';
        $classViewPath = $moduleRoot.'/resources/views/livewire';

        // Not Livewire::addNamespace() directly: SynapseServiceProvider rebinds
        // 'livewire.finder' in its own register(), so a namespace added to the
        // finder that exists right now is lost for any provider registered
        // before it (package modules such as synapps-* load first). This runs
        // against the current finder, if any, and again for a replacement.
        $this->callAfterResolving('livewire.finder', function ($finder) use ($prefix, $componentsPath, $livewireNamespace, $livewirePath, $classViewPath) {
            $finder->addNamespace(
                $prefix,
                viewPath: is_dir($componentsPath) ? $componentsPath : null,
                classNamespace: $livewireNamespace,
                classPath: is_dir($livewirePath) ? $livewirePath : null,
                classViewPath: is_dir($classViewPath) ? $classViewPath : null,
            );
        });
    }

    /**
     * Auto-register class-based Blade components from src/View/Components/ directory
     *
     * Scans recursively and registers components with module prefix:
     * - src/View/Components/Alert.php → <x-example::alert />
     * - src/View/Components/Forms/Input.php → <x-example::forms.input />
     */
    protected function registerBladeComponents(): void
    {
        $reflection = new ReflectionClass($this);
        $namespace = $reflection->getNamespaceName();
        $basePath = dirname($reflection->getFileName());

        $componentsPath = $basePath.'/View/Components';

        if (! is_dir($componentsPath)) {
            return;
        }

        $prefix = $this->getComponentPrefix();
        $componentNamespace = $namespace.'\\View\\Components';

        // Recursively scan for Blade component classes
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($componentsPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                // Get relative path from components directory
                $relativePath = str_replace($componentsPath.DIRECTORY_SEPARATOR, '', $file->getPathname());
                $relativePath = str_replace('.php', '', $relativePath);
                $relativePath = str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);

                $componentClass = $componentNamespace.'\\'.$relativePath;

                if (class_exists($componentClass)) {
                    // Convert to kebab-case with dots for nested: forms.input
                    // Split by namespace separator, convert each segment to kebab-case, then join with dots
                    $segments = explode('\\', $relativePath);
                    $segments = array_map(fn ($segment) => Str::kebab($segment), $segments);
                    $componentTag = implode('.', $segments);

                    // Register with prefix: <x-example::alert />
                    app('blade.compiler')->component($componentClass, $prefix.'::'.$componentTag);
                }
            }
        }
    }

    /**
     * Get the component prefix for registration
     *
     * Auto-detects from namespace:
     * - VmEngine\Example\ExampleServiceProvider → 'example'
     * - SynApps\Modules\Home\HomeServiceProvider → 'home'
     */
    protected function getComponentPrefix(): string
    {
        if ($this->componentPrefix !== null) {
            return $this->componentPrefix;
        }

        $reflection = new ReflectionClass($this);
        $namespace = $reflection->getNamespaceName();

        // Extract module name from namespace
        // VmEngine\Example → Example
        // SynApps\Modules\Home → Home
        $parts = explode('\\', $namespace);
        $moduleName = end($parts);

        // Convert to snake_case
        $this->componentPrefix = Str::snake($moduleName);

        return $this->componentPrefix;
    }
}
