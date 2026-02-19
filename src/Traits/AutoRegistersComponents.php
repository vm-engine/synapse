<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Traits;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use Livewire\Livewire;
use ReflectionClass;

/**
 * Auto-register Livewire and Blade components for Synapse modules
 *
 * This trait automatically discovers and registers:
 * - Livewire MFC components from resources/views/components/ directory (Livewire v4)
 * - Livewire class-based components from src/Livewire/ directory (legacy)
 * - Class-based Blade components from src/View/Components/ directory
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
            $this->registerMfcComponents();
            $this->registerLivewireComponents();
        }

        if ($this->autoRegisterBladeComponents) {
            $this->registerBladeComponents();
        }
    }

    /**
     * Auto-register Livewire v4 MFC components from resources/views/components/ directory
     *
     * Scans for directories containing a .php file (anonymous class) alongside a .blade.php file:
     * - resources/views/components/todo-form/todo-form.php → 'todos.todo-form'
     * - resources/views/components/config-list/config-list.php → 'todos.config-list'
     */
    protected function registerMfcComponents(): void
    {
        $reflection = new ReflectionClass($this);
        $basePath = dirname($reflection->getFileName());

        // MFC components live in resources/views/components/ relative to the module's src/ dir
        $componentsPath = dirname($basePath).'/resources/views/components';

        if (! is_dir($componentsPath)) {
            return;
        }

        $prefix = $this->getComponentPrefix();

        // Scan for MFC directories (directories containing a .php + .blade.php pair)
        $iterator = new \DirectoryIterator($componentsPath);

        foreach ($iterator as $item) {
            if (! $item->isDir() || $item->isDot()) {
                continue;
            }

            $dirName = $item->getFilename();
            $phpFile = $item->getPathname().'/'.$dirName.'.php';
            $bladeFile = $item->getPathname().'/'.$dirName.'.blade.php';

            // MFC component: directory with both .php and .blade.php files
            if (file_exists($phpFile) && file_exists($bladeFile)) {
                $componentName = $prefix.'.'.$dirName;

                // Register the MFC component directory with Livewire's view resolver
                // Must use viewPath (not class) so Livewire resolves it as a multi-file component
                Livewire::addComponent($componentName, viewPath: $item->getPathname());
            }
        }
    }

    /**
     * Auto-register all Livewire components from src/Livewire/ directory
     *
     * Scans recursively and registers components with module prefix:
     * - src/Livewire/ExampleForm.php → 'example.example-form'
     * - src/Livewire/Components/ButtonSample.php → 'example.components.button-sample'
     */
    protected function registerLivewireComponents(): void
    {
        $reflection = new ReflectionClass($this);
        $namespace = $reflection->getNamespaceName();
        $basePath = dirname($reflection->getFileName());

        $livewirePath = $basePath.'/Livewire';

        if (! is_dir($livewirePath)) {
            return;
        }

        $prefix = $this->getComponentPrefix();
        $livewireNamespace = $namespace.'\\Livewire';

        // Recursively scan for Livewire component classes
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($livewirePath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                // Get relative path from Livewire directory
                $relativePath = str_replace($livewirePath.DIRECTORY_SEPARATOR, '', $file->getPathname());
                $relativePath = str_replace('.php', '', $relativePath);
                $relativePath = str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);

                $componentClass = $livewireNamespace.'\\'.$relativePath;

                if (class_exists($componentClass)) {
                    // Convert to kebab-case with dots for nested: components.button-sample
                    // Split by namespace separator, convert each segment to kebab-case, then join with dots
                    $segments = explode('\\', $relativePath);
                    $segments = array_map(fn ($segment) => Str::kebab($segment), $segments);
                    $componentName = implode('.', $segments);

                    // Register with prefix: example.components.button-sample
                    Livewire::component($prefix.'.'.$componentName, $componentClass);
                }
            }
        }
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

        // Convert to kebab-case
        $this->componentPrefix = Str::kebab($moduleName);

        return $this->componentPrefix;
    }
}
