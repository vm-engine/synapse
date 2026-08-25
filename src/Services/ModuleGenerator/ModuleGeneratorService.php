<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Services\ModuleGenerator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class ModuleGeneratorService
{
    protected Filesystem $files;

    protected string $basePath;

    /** @var array<string, mixed> */
    protected array $moduleConfig = [];

    protected bool $isPackage = false;

    protected string $vendor = 'VmEngine';

    public function __construct(
        public NamespaceResolver $namespaceResolver,
        public StubRenderer $stubRenderer
    ) {
        $this->files = new Filesystem;

        // Set default base path - use Laravel base_path if available, otherwise current directory
        if (function_exists('base_path') && function_exists('app')) {
            try {
                $this->basePath = base_path('synapps/modules');
            } catch (\Throwable $e) {
                // Laravel not bootstrapped, use current directory
                $this->basePath = getcwd();
            }
        } else {
            // Running standalone without Laravel
            $this->basePath = getcwd();
        }
    }

    /**
     * Set the base path for module generation
     * Supports both directory and package modules
     */
    public function setBasePath(string $path): self
    {
        $this->basePath = rtrim($path, '/\\');

        // Detect if this is a package module based on path
        $this->isPackage = str_contains($path, 'vendor') || str_contains($path, 'packages');

        return $this;
    }

    /**
     * Set or load module configuration
     */
    /** @param  array<string, mixed>  $config */
    public function setModuleConfig(array $config): self
    {
        $this->moduleConfig = $config;

        return $this;
    }

    /**
     * Set vendor name for package modules
     */
    public function setVendor(string $vendor): self
    {
        $this->vendor = $vendor;

        return $this;
    }

    /**
     * Generate complete module structure
     */
    /** @param  array<string, mixed>  $options */
    public function generateModule(string $name, array $options = []): bool
    {
        $modulePath = $this->getModulePath($name);

        // Check if module already exists
        if ($this->files->isDirectory($modulePath)) {
            throw new \RuntimeException("Module '{$name}' already exists at: {$modulePath}");
        }

        // Create module directory structure
        $this->createModuleDirectories($modulePath);

        // Generate module.json
        $this->generateModuleJson($modulePath, $name, $options);

        // Generate ServiceProvider
        $this->generateServiceProvider($modulePath, $name);

        // Create route files
        $this->createRouteFiles($modulePath);

        // Create basic language file
        $this->createLanguageFiles($modulePath, $name);

        // Optionally create example components
        if ($options['with_model'] ?? false) {
            $this->generateComponent('model', $name, ['name' => $name]);
        }

        if ($options['with_controller'] ?? false) {
            $this->generateComponent('controller', $name.'Controller', ['module' => $name]);
        }

        if ($options['with_livewire'] ?? false) {
            $this->generateComponent('livewire', $name.'List', ['module' => $name]);
        }

        return true;
    }

    /**
     * Generate individual component within module
     */
    /** @param  array<string, mixed>  $options */
    public function generateComponent(string $type, string $name, array $options = []): string
    {
        $moduleName = $options['module'] ?? '';

        if (empty($moduleName)) {
            throw new \InvalidArgumentException('Module name is required');
        }

        $modulePath = $this->getModulePath($moduleName);

        if (! $this->files->isDirectory($modulePath)) {
            throw new \RuntimeException("Module '{$moduleName}' does not exist at: {$modulePath}");
        }

        // Load module config if not already loaded
        if (empty($this->moduleConfig)) {
            $this->loadModuleConfig($modulePath);
        }

        // Determine stub name
        $stubName = $this->getStubName($type, $options);

        // Generate variables for stub
        $variables = $this->prepareVariables($moduleName, $type, $name, $options);

        // Render stub
        $content = $this->stubRenderer->render($stubName, $variables);

        // Get component path
        $componentPath = $this->getComponentPath($type, $name, $modulePath, $moduleName);

        // Ensure directory exists
        $this->ensureDirectoryExists(dirname($componentPath));

        // Write file
        $this->files->put($componentPath, $content);

        // Auto-generate view file for non-inline Livewire components
        if ($type === 'livewire' && ! ($options['inline'] ?? false)) {
            $this->generateLivewireView($name, $moduleName, $modulePath);
        }

        return $componentPath;
    }

    /**
     * Generate view file for Livewire component
     */
    protected function generateLivewireView(string $name, string $moduleName, string $modulePath): void
    {
        try {
            // Generate variables for view
            $variables = $this->prepareVariables($moduleName, 'livewire-view', $name, []);

            // Render view stub
            $content = $this->stubRenderer->render('livewire/view', $variables);

            // Get view path
            $viewPath = $this->getComponentPath('livewire-view', $name, $modulePath, $moduleName);

            // Ensure directory exists
            $this->ensureDirectoryExists(dirname($viewPath));

            // Write view file
            $this->files->put($viewPath, $content);
        } catch (\Exception $e) {
            // Silent fail - view generation is optional
            // The component class is the critical part
        }
    }

    /**
     * Get the full path for a component
     */
    public function getComponentPath(
        string $type,
        string $name,
        ?string $modulePath = null,
        ?string $moduleName = null
    ): string {
        $modulePath = $modulePath ?? $this->basePath;
        $subdir = $this->namespaceResolver->getComponentSubdirectory($type);

        // Handle migration special case (needs timestamp)
        if ($type === 'migration') {
            $timestamp = date('Y_m_d_His');
            $fileName = $timestamp.'_'.$name.'.php';

            return $modulePath.'/'.$subdir.'/'.$fileName;
        }

        // Handle Livewire view special case
        if ($type === 'livewire-view') {
            $moduleSlug = Str::slug($moduleName ?? '');
            // Handle subdirectories: Admin/PostList => admin/post-list (filesystem paths use slashes)
            $cleanName = str_replace(['Component', 'Livewire'], '', $name);
            $viewParts = explode('/', $cleanName);
            $viewParts = array_map(fn ($part) => Str::kebab($part), $viewParts);
            $viewPath = implode('/', $viewParts);

            return $modulePath.'/resources/views/livewire/'.$viewPath.'.blade.php';
        }

        // Handle component view special case
        if ($type === 'component-view') {
            $viewName = strtolower(str_replace(['\\', '/'], '.', $name));

            return $modulePath.'/'.$subdir.'/'.$viewName.'.blade.php';
        }

        // Handle view special case
        if ($type === 'view') {
            $viewName = strtolower(str_replace(['\\', '/'], '/', $name));

            return $modulePath.'/'.$subdir.'/'.$viewName.'.blade.php';
        }

        // Handle config special case
        if ($type === 'config') {
            $configName = strtolower(str_replace(['\\', '/'], '/', $name));

            return $modulePath.'/'.$subdir.'/'.$configName.'.php';
        }

        // Generate class name
        $className = $this->namespaceResolver->generateClassName($name, $type);

        // Convert namespace separators to directory separators for file path
        $classPath = str_replace('\\', '/', $className);

        return $modulePath.'/'.$subdir.'/'.$classPath.'.php';
    }

    /**
     * Determine if module is package-based
     */
    public function isPackageModule(): bool
    {
        return $this->isPackage;
    }

    /**
     * Get module path
     */
    protected function getModulePath(string $name): string
    {
        // For package modules, use the base path as-is
        if ($this->isPackage) {
            return $this->basePath;
        }

        // For directory modules, if basePath already includes the module, don't append
        if (str_contains($this->basePath, '/'.Str::snake($name))) {
            return $this->basePath;
        }

        // Otherwise, append module name to base path
        return $this->basePath.'/'.Str::snake($name);
    }

    /**
     * Create module directory structure
     */
    protected function createModuleDirectories(string $modulePath): void
    {
        $directories = [
            'src',
            'src/Http/Controllers',
            'src/Livewire',
            'src/Models',
            'routes',
            'resources/views',
            'resources/views/livewire',
            'resources/views/components',
            'database/migrations',
            'database/factories',
            'database/seeders',
            'lang/en',
            'tests/Feature',
            'tests/Unit',
        ];

        foreach ($directories as $dir) {
            $this->ensureDirectoryExists($modulePath.'/'.$dir);
        }
    }

    /**
     * Generate module.json configuration file
     */
    /** @param  array<string, mixed>  $options */
    protected function generateModuleJson(string $modulePath, string $name, array $options): void
    {
        $idCases = $this->namespaceResolver->generateIdCases($name);

        $config = [
            'id' => $idCases['snake'],
            'id_case' => $idCases,
            'name' => $options['display_name'] ?? Str::title(str_replace('_', ' ', $idCases['snake'])),
            'description' => $options['description'] ?? '',
            'version' => '0.1.0',
            'author' => $options['author'] ?? '',
            'email' => $options['email'] ?? '',
            'git_repo' => $options['git_repo'] ?? '',
            'url_prefix' => $options['url_prefix'] ?? $idCases['slug'],
            'load_priority' => $options['load_priority'] ?? 99,
            'dependencies' => [
                'npm' => [
                    'main' => new \stdClass,
                    'dev' => new \stdClass,
                ],
                'composer' => [
                    'main' => new \stdClass,
                    'dev' => new \stdClass,
                ],
            ],
        ];

        $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $this->files->put($modulePath.'/module.json', $json);

        $this->moduleConfig = $config;
    }

    /**
     * Generate ServiceProvider
     */
    protected function generateServiceProvider(string $modulePath, string $name): void
    {
        $idCases = $this->namespaceResolver->generateIdCases($name);
        $className = $idCases['pascal'].'ServiceProvider';

        $namespace = $this->isPackage
            ? $this->namespaceResolver->resolvePackageNamespace($name, '', $this->vendor)
            : $this->namespaceResolver->resolveDirectoryNamespace($name);

        $variables = [
            'namespace' => $namespace,
            'class' => $className,
            'module' => $idCases['snake'],
            'modulePascal' => $idCases['pascal'],
        ];

        $content = $this->stubRenderer->render('module/ServiceProvider', $variables);

        $this->files->put($modulePath.'/src/'.$className.'.php', $content);
    }

    /**
     * Create route files
     */
    protected function createRouteFiles(string $modulePath): void
    {
        $routeFiles = ['web.php', 'web.backend.php', 'api.php'];

        foreach ($routeFiles as $file) {
            $path = $modulePath.'/routes/'.$file;
            if (! $this->files->exists($path)) {
                $this->files->put($path, "<?php\n\n// Add routes here\n");
            }
        }
    }

    /**
     * Create language files
     */
    protected function createLanguageFiles(string $modulePath, string $name): void
    {
        $idCases = $this->namespaceResolver->generateIdCases($name);

        $langFiles = [
            'menu.php' => "<?php\n\nreturn [\n    // Menu translations\n];\n",
            'acl.php' => "<?php\n\nreturn [\n    // ACL translations\n];\n",
        ];

        foreach ($langFiles as $file => $content) {
            $path = $modulePath.'/lang/en/'.$file;
            if (! $this->files->exists($path)) {
                $this->files->put($path, $content);
            }
        }
    }

    /**
     * Load module configuration from module.json
     */
    protected function loadModuleConfig(string $modulePath): void
    {
        $configPath = $modulePath.'/module.json';

        if ($this->files->exists($configPath)) {
            $this->moduleConfig = json_decode($this->files->get($configPath), true);
        }
    }

    /**
     * Determine stub name based on component type and options
     */
    /** @param  array<string, mixed>  $options */
    protected function getStubName(string $type, array $options): string
    {
        $stubMap = [
            'model' => $options['pivot'] ?? false ? 'model/model.pivot' : 'model/model',
            'controller' => $this->getControllerStub($options),
            'migration' => $this->getMigrationStub($options),
            'livewire' => $options['inline'] ?? false ? 'livewire/component.inline' : 'livewire/component',
            'livewire-view' => 'livewire/view',
            'factory' => 'factory/factory',
            'seeder' => 'seeder/seeder',
            'request' => 'request/request',
            'middleware' => 'middleware/middleware',
            'event' => 'event/event',
            'listener' => 'listener/listener',
            'job' => 'job/job',
            'provider' => 'provider/provider',
            'policy' => 'policy/policy',
            'notification' => 'notification/notification',
            'mail' => 'mail/mail',
            'test' => $options['unit'] ?? false ? 'test/test.unit' : 'test/test.feature',
            // New component types
            'cast' => 'cast/cast',
            'channel' => 'channel/channel',
            'command' => 'command/command',
            'component' => 'component/component',
            'component-view' => 'component/component-view',
            'config' => 'config/config',
            'enum' => 'enum/enum',
            'interface' => 'interface/interface',
            'job-middleware' => 'job-middleware/job-middleware',
            'observer' => 'observer/observer',
            'resource' => $options['collection'] ?? false ? 'resource/resource-collection' : 'resource/resource',
            'rule' => 'rule/rule',
            'scope' => 'scope/scope',
            'trait' => 'trait/trait',
            'view' => 'view/view',
            'class' => $options['invokable'] ?? false ? 'class/class.invokable' : 'class/class',
            'exception' => $this->getExceptionStub($options),
            'data' => 'data/data',
        ];

        return $stubMap[$type] ?? $type.'/'.$type;
    }

    /**
     * Get exception stub variant
     */
    /** @param  array<string, mixed>  $options */
    protected function getExceptionStub(array $options): string
    {
        $render = $options['render'] ?? false;
        $report = $options['report'] ?? false;

        if ($render && $report) {
            return 'exception/exception.render-report';
        }

        if ($render) {
            return 'exception/exception.render';
        }

        if ($report) {
            return 'exception/exception.report';
        }

        return 'exception/exception';
    }

    /**
     * Get controller stub variant
     */
    /** @param  array<string, mixed>  $options */
    protected function getControllerStub(array $options): string
    {
        if ($options['api'] ?? false) {
            return 'controller/controller.api';
        }

        if ($options['invokable'] ?? false) {
            return 'controller/controller.invokable';
        }

        if (($options['resource'] ?? false) || isset($options['model'])) {
            return 'controller/controller.resource';
        }

        return 'controller/controller';
    }

    /**
     * Get migration stub variant
     */
    /** @param  array<string, mixed>  $options */
    protected function getMigrationStub(array $options): string
    {
        if (isset($options['create'])) {
            return 'migration/migration.create';
        }

        if (isset($options['table'])) {
            return 'migration/migration.update';
        }

        return 'migration/migration';
    }

    /**
     * Prepare variables for stub rendering
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    protected function prepareVariables(
        string $moduleName,
        string $type,
        string $name,
        array $options
    ): array {
        $idCases = $this->namespaceResolver->generateIdCases($moduleName);
        $className = $this->namespaceResolver->generateClassName($name, $type);

        // Handle subdirectories in class name
        // Extract actual class name and namespace path from className (e.g., Admin\PostList => Admin, PostList)
        $classNameParts = explode('\\', $className);
        $actualClassName = array_pop($classNameParts); // Get last part (PostList)
        $subNamespace = implode('\\', $classNameParts); // Get path parts (Admin)

        // Base namespace for the module
        $baseNamespace = $this->isPackage
            ? $this->namespaceResolver->resolvePackageNamespace($moduleName, '', $this->vendor)
            : $this->namespaceResolver->resolveDirectoryNamespace($moduleName);

        // Component-specific namespace
        $componentNamespace = $this->namespaceResolver->getComponentNamespace($type);
        $fullNamespace = empty($componentNamespace)
            ? $baseNamespace
            : $baseNamespace.'\\'.$componentNamespace;

        // Append subdirectory namespace if exists
        if (! empty($subNamespace)) {
            $fullNamespace .= '\\'.$subNamespace;
        }

        // Factory and seeder namespaces (special cases)
        $factoryNamespace = $this->namespaceResolver->resolveFactoryNamespace(
            $moduleName,
            $this->isPackage,
            $this->vendor
        );

        $seederNamespace = $this->namespaceResolver->resolveSeederNamespace(
            $moduleName,
            $this->isPackage,
            $this->vendor
        );

        // Table name (for models/migrations)
        $tableName = $options['table'] ?? $options['create'] ??
                     $this->namespaceResolver->generateTableName($name);

        // Model name (for controllers/factories)
        // Strip common suffixes to get base model name
        $baseName = preg_replace('/(Factory|Controller|Policy|Seeder)$/', '', $name);
        $modelName = $options['model'] ?? Str::studly($baseName);

        // Observers type-hint a specific model and need a real `use` import,
        // since the model normally lives in a different namespace
        // ({rootNamespace}\Models) than the observer itself. A short --model
        // name resolves against the module's own Models namespace; a name
        // that already contains a namespace separator is used as-is. With no
        // --model given, fall back to the base Eloquent Model.
        $modelImport = '';
        $modelVariable = '';
        if ($type === 'observer') {
            $modelFqcn = isset($options['model']) && $options['model'] !== ''
                ? (str_contains($options['model'], '\\')
                    ? $options['model']
                    : $baseNamespace.'\\Models\\'.$options['model'])
                : Model::class;

            $modelName = class_basename($modelFqcn);
            $modelVariable = lcfirst($modelName);
            $modelImport = "use {$modelFqcn};";

            // Already fully resolved above — don't let the raw --model value
            // (which may be a FQCN) override the computed short class name
            // in the final options merge below.
            unset($options['model']);
        }

        // View name (for Livewire components)
        // Handle subdirectories: Admin/PostList => admin.post-list (dots for Blade view paths)
        $cleanName = str_replace(['Component', 'Livewire'], '', $name);
        $viewParts = explode('/', $cleanName);
        $viewParts = array_map(fn ($part) => Str::kebab($part), $viewParts);
        $viewName = implode('.', $viewParts);

        $variables = [
            'namespace' => $fullNamespace,
            'rootNamespace' => $baseNamespace,
            'class' => $actualClassName,
            'module' => $idCases['snake'],
            'modulePascal' => $idCases['pascal'],
            'moduleSnake' => $idCases['snake'],
            'moduleSlug' => $idCases['slug'],
            'moduleStudly' => $idCases['studly'],
            'table' => $tableName,
            'model' => $modelName,
            'modelImport' => $modelImport,
            'modelVariable' => $modelVariable,
            'view' => $viewName,
            'factoryNamespace' => $factoryNamespace,
            'seederNamespace' => $seederNamespace,
        ];

        // Merge with custom options (allows commands to pass additional variables)
        // Filter out null values to avoid overwriting computed variables (e.g., 'table' when --create is used)
        return array_merge($variables, array_filter($options, fn ($v) => $v !== null));
    }

    /**
     * Ensure directory exists
     */
    protected function ensureDirectoryExists(string $path): void
    {
        if (! $this->files->isDirectory($path)) {
            $this->files->makeDirectory($path, 0755, true);
        }
    }

    /**
     * Run synapps:discover command
     */
    public function runDiscovery(): void
    {
        Artisan::call('synapps:discover');
    }

    /**
     * Check if discovery should run for component type
     */
    public function shouldRunDiscovery(string $componentType): bool
    {
        return in_array($componentType, ['module', 'migration', 'seeder', 'factory']);
    }
}
