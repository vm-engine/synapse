#!/usr/bin/env php
<?php

/**
 * Universal IDE Helper Generator for vm-engine/synapse
 *
 * This script generates PHPDoc annotations for better IDE autocompletion
 * by analyzing facades, models, and helper functions from the package
 * and its dependencies.
 *
 * Usage: php generate-ide-helper.php
 * Output: _ide_helper.php
 */

require_once __DIR__ . '/vendor/autoload.php';

class IdeHelperGenerator
{
    protected string $output = '';
    protected array $facades = [];
    protected array $models = [];
    protected array $helpers = [];
    protected array $serviceProviders = [];

    public function __construct()
    {
        $this->output = "<?php\n\n";
        $this->output .= "/**\n";
        $this->output .= " * IDE Helper for vm-engine/synapse\n";
        $this->output .= " * Generated: " . date('Y-m-d H:i:s') . "\n";
        $this->output .= " * \n";
        $this->output .= " * This file provides autocompletion support for:\n";
        $this->output .= " * - Laravel Facades\n";
        $this->output .= " * - Eloquent Models\n";
        $this->output .= " * - Helper Functions\n";
        $this->output .= " * - Service Container Bindings\n";
        $this->output .= " * \n";
        $this->output .= " * @see https://github.com/barryvdh/laravel-ide-helper\n";
        $this->output .= " */\n\n";
        $this->output .= "namespace PHPSTORM_META {\n\n";
        $this->output .= "    override(\\Illuminate\\Support\\Facades\\Facade::getFacadeRoot(), map([\n";
        $this->output .= "        '' => '@',\n";
        $this->output .= "    ]));\n\n";
        $this->output .= "}\n\n";
        $this->output .= "namespace {\n\n";
    }

    public function generate(): void
    {
        echo "🔍 Scanning package structure...\n";

        $this->discoverFacades();
        $this->discoverModels();
        $this->discoverHelpers();
        $this->discoverServiceProviders();

        $this->generateFacadeHelpers();
        $this->generateModelHelpers();
        $this->generateHelperFunctions();
        $this->generateServiceContainerHelpers();

        $this->output .= "}\n";

        $this->writeFile();

        echo "✓ IDE helper generated successfully: _ide_helper.php\n";
        echo "📊 Summary:\n";
        echo "   - Facades: " . count($this->facades) . "\n";
        echo "   - Models: " . count($this->models) . "\n";
        echo "   - Helpers: " . count($this->helpers) . "\n";
        echo "   - Service Providers: " . count($this->serviceProviders) . "\n";
    }

    protected function discoverFacades(): void
    {
        echo "  → Discovering facades...\n";

        // Laravel Core Facades
        $laravelFacades = [
            'App' => 'Illuminate\\Foundation\\Application',
            'Artisan' => 'Illuminate\\Contracts\\Console\\Kernel',
            'Auth' => 'Illuminate\\Auth\\AuthManager',
            'Blade' => 'Illuminate\\View\\Compilers\\BladeCompiler',
            'Broadcast' => 'Illuminate\\Contracts\\Broadcasting\\Factory',
            'Bus' => 'Illuminate\\Contracts\\Bus\\Dispatcher',
            'Cache' => 'Illuminate\\Cache\\CacheManager',
            'Config' => 'Illuminate\\Config\\Repository',
            'Cookie' => 'Illuminate\\Cookie\\CookieJar',
            'Crypt' => 'Illuminate\\Encryption\\Encrypter',
            'DB' => 'Illuminate\\Database\\DatabaseManager',
            'Event' => 'Illuminate\\Events\\Dispatcher',
            'File' => 'Illuminate\\Filesystem\\Filesystem',
            'Gate' => 'Illuminate\\Contracts\\Auth\\Access\\Gate',
            'Hash' => 'Illuminate\\Contracts\\Hashing\\Hasher',
            'Http' => 'Illuminate\\Http\\Client\\Factory',
            'Lang' => 'Illuminate\\Translation\\Translator',
            'Log' => 'Illuminate\\Log\\LogManager',
            'Mail' => 'Illuminate\\Mail\\Mailer',
            'Notification' => 'Illuminate\\Notifications\\ChannelManager',
            'Password' => 'Illuminate\\Auth\\Passwords\\PasswordBrokerManager',
            'Queue' => 'Illuminate\\Queue\\QueueManager',
            'Redirect' => 'Illuminate\\Routing\\Redirector',
            'Redis' => 'Illuminate\\Redis\\RedisManager',
            'Request' => 'Illuminate\\Http\\Request',
            'Response' => 'Illuminate\\Contracts\\Routing\\ResponseFactory',
            'Route' => 'Illuminate\\Routing\\Router',
            'Schema' => 'Illuminate\\Database\\Schema\\Builder',
            'Session' => 'Illuminate\\Session\\SessionManager',
            'Storage' => 'Illuminate\\Filesystem\\FilesystemManager',
            'URL' => 'Illuminate\\Routing\\UrlGenerator',
            'Validator' => 'Illuminate\\Validation\\Factory',
            'View' => 'Illuminate\\View\\Factory',
        ];

        // Livewire Facade
        if (class_exists('Livewire\\Livewire')) {
            $laravelFacades['Livewire'] = 'Livewire\\LivewireManager';
        }

        $this->facades = $laravelFacades;
    }

    protected function discoverModels(): void
    {
        echo "  → Discovering Eloquent models...\n";

        // Scan src directory for models
        $srcPath = __DIR__ . '/src';
        $this->scanForModels($srcPath);

        // Scan vendor for common model locations
        $vendorPaths = [
            __DIR__ . '/vendor/laravel/framework/src/Illuminate/Foundation/Auth',
        ];

        foreach ($vendorPaths as $path) {
            if (is_dir($path)) {
                $this->scanForModels($path);
            }
        }
    }

    protected function scanForModels(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $content = file_get_contents($file->getPathname());

                // Look for classes extending Model
                if (preg_match('/namespace\s+([^;]+);/i', $content, $namespaceMatch)) {
                    if (preg_match('/class\s+(\w+)\s+extends\s+Model/i', $content, $classMatch)) {
                        $namespace = trim($namespaceMatch[1]);
                        $className = $classMatch[1];
                        $fullClass = $namespace . '\\' . $className;

                        $this->models[] = [
                            'class' => $fullClass,
                            'name' => $className,
                            'path' => $file->getPathname(),
                        ];
                    }
                }
            }
        }
    }

    protected function discoverHelpers(): void
    {
        echo "  → Discovering helper functions...\n";

        $helperFile = __DIR__ . '/src/helper.php';

        if (file_exists($helperFile)) {
            $content = file_get_contents($helperFile);

            // Match function definitions
            preg_match_all('/function\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\((.*?)\)/s', $content, $matches);

            if (!empty($matches[1])) {
                foreach ($matches[1] as $index => $functionName) {
                    $params = $matches[2][$index] ?? '';

                    $this->helpers[] = [
                        'name' => $functionName,
                        'params' => $params,
                        'file' => $helperFile,
                    ];
                }
            }
        }
    }

    protected function discoverServiceProviders(): void
    {
        echo "  → Discovering service providers...\n";

        $composerJson = json_decode(file_get_contents(__DIR__ . '/composer.json'), true);

        if (isset($composerJson['extra']['laravel']['providers'])) {
            $this->serviceProviders = $composerJson['extra']['laravel']['providers'];
        }
    }

    protected function generateFacadeHelpers(): void
    {
        $this->output .= "    /**\n";
        $this->output .= "     * Laravel Facade Helpers\n";
        $this->output .= "     * ======================================\n";
        $this->output .= "     */\n\n";

        foreach ($this->facades as $alias => $class) {
            echo "  → Analyzing {$alias} facade...\n";

            $methods = $this->extractClassMethods($class);

            $this->output .= "    /**\n";
            $this->output .= "     * @see \\{$class}\n";

            if (!empty($methods)) {
                $this->output .= "     * \n";
                foreach ($methods as $method) {
                    $this->output .= "     * {$method}\n";
                }
            }

            $this->output .= "     */\n";
            $this->output .= "    class {$alias} extends \\Illuminate\\Support\\Facades\\Facade {}\n\n";
        }
    }

    protected function extractClassMethods(string $className): array
    {
        $methods = [];

        if (!class_exists($className) && !interface_exists($className)) {
            return $methods;
        }

        try {
            $reflection = new ReflectionClass($className);

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                // Skip magic methods and constructor
                if (str_starts_with($method->getName(), '__')) {
                    continue;
                }

                // Skip inherited methods from base classes (limit to direct class methods)
                if ($method->getDeclaringClass()->getName() !== $className) {
                    // But include interface methods
                    if (!$reflection->isInterface()) {
                        continue;
                    }
                }

                $methodDoc = $this->buildMethodDocumentation($method);
                if ($methodDoc) {
                    $methods[] = $methodDoc;
                }
            }
        } catch (ReflectionException $e) {
            // Class doesn't exist or can't be reflected
        }

        return $methods;
    }

    protected function buildMethodDocumentation(ReflectionMethod $method): string
    {
        $params = [];
        $returnType = 'mixed';

        // Build parameters
        foreach ($method->getParameters() as $param) {
            $paramStr = '';

            // Get parameter type
            if ($param->hasType()) {
                $type = $param->getType();
                if ($type instanceof ReflectionNamedType) {
                    $paramStr .= ($type->allowsNull() ? '?' : '') . $type->getName() . ' ';
                } elseif ($type instanceof ReflectionUnionType) {
                    $paramStr .= implode('|', array_map(fn($t) => $t->getName(), $type->getTypes())) . ' ';
                }
            }

            $paramStr .= '$' . $param->getName();

            // Add default value
            if ($param->isOptional() && !$param->isVariadic()) {
                try {
                    $default = $param->getDefaultValue();
                    if (is_null($default)) {
                        $paramStr .= ' = null';
                    } elseif (is_bool($default)) {
                        $paramStr .= ' = ' . ($default ? 'true' : 'false');
                    } elseif (is_string($default)) {
                        $paramStr .= ' = \'' . addslashes($default) . '\'';
                    } elseif (is_array($default)) {
                        $paramStr .= ' = []';
                    } else {
                        $paramStr .= ' = ' . var_export($default, true);
                    }
                } catch (ReflectionException $e) {
                    // Can't get default value
                }
            }

            if ($param->isVariadic()) {
                $paramStr = '...' . $paramStr;
            }

            $params[] = $paramStr;
        }

        // Get return type
        if ($method->hasReturnType()) {
            $type = $method->getReturnType();
            if ($type instanceof ReflectionNamedType) {
                $returnType = ($type->allowsNull() ? '?' : '') . $type->getName();
            } elseif ($type instanceof ReflectionUnionType) {
                $returnType = implode('|', array_map(fn($t) => $t->getName(), $type->getTypes()));
            }
        }

        // Normalize return type for better IDE support
        $returnType = str_replace('self', '\\' . $method->getDeclaringClass()->getName(), $returnType);

        $static = $method->isStatic() ? 'static ' : '';

        return "@method {$static}{$returnType} {$method->getName()}(" . implode(', ', $params) . ")";
    }

    protected function generateModelHelpers(): void
    {
        if (empty($this->models)) {
            return;
        }

        $this->output .= "    /**\n";
        $this->output .= "     * Eloquent Model Helpers\n";
        $this->output .= "     * ======================================\n";
        $this->output .= "     * \n";
        $this->output .= "     * These provide better autocompletion for model methods\n";
        $this->output .= "     */\n\n";

        foreach ($this->models as $model) {
            $this->output .= "    /**\n";
            $this->output .= "     * {$model['name']} Model\n";
            $this->output .= "     * \n";
            $this->output .= "     * @see {$model['class']}\n";
            $this->output .= "     * \n";
            $this->output .= "     * @method static \\Illuminate\\Database\\Eloquent\\Builder|{$model['class']} newModelQuery()\n";
            $this->output .= "     * @method static \\Illuminate\\Database\\Eloquent\\Builder|{$model['class']} newQuery()\n";
            $this->output .= "     * @method static \\Illuminate\\Database\\Eloquent\\Builder|{$model['class']} query()\n";
            $this->output .= "     * @method static \\Illuminate\\Database\\Eloquent\\Builder|{$model['class']} where(\$column, \$operator = null, \$value = null, \$boolean = 'and')\n";
            $this->output .= "     * @method static \\Illuminate\\Database\\Eloquent\\Builder|{$model['class']} whereIn(\$column, \$values, \$boolean = 'and', \$not = false)\n";
            $this->output .= "     * @method static {$model['class']}|null find(\$id, \$columns = ['*'])\n";
            $this->output .= "     * @method static {$model['class']} findOrFail(\$id, \$columns = ['*'])\n";
            $this->output .= "     * @method static {$model['class']} create(array \$attributes = [])\n";
            $this->output .= "     * @method static \\Illuminate\\Database\\Eloquent\\Collection|{$model['class']}[] all(\$columns = ['*'])\n";
            $this->output .= "     * @method static \\Illuminate\\Database\\Eloquent\\Collection|{$model['class']}[] get(\$columns = ['*'])\n";
            $this->output .= "     * @mixin \\Illuminate\\Database\\Eloquent\\Builder\n";
            $this->output .= "     */\n";
            $this->output .= "    class {$model['name']} extends \\Illuminate\\Database\\Eloquent\\Model {}\n\n";
        }
    }

    protected function generateHelperFunctions(): void
    {
        if (empty($this->helpers)) {
            return;
        }

        $this->output .= "    /**\n";
        $this->output .= "     * Helper Functions\n";
        $this->output .= "     * ======================================\n";
        $this->output .= "     */\n\n";

        foreach ($this->helpers as $helper) {
            // Extract parameter types for better documentation
            $paramDocs = $this->parseParameters($helper['params']);

            $this->output .= "    /**\n";
            $this->output .= "     * {$helper['name']} helper function\n";
            if (!empty($paramDocs)) {
                foreach ($paramDocs as $paramDoc) {
                    $this->output .= "     * @param {$paramDoc}\n";
                }
            }
            $this->output .= "     * @return mixed\n";
            $this->output .= "     */\n";
            $this->output .= "    function {$helper['name']}({$helper['params']}) {}\n\n";
        }
    }

    protected function generateServiceContainerHelpers(): void
    {
        $this->output .= "    /**\n";
        $this->output .= "     * Service Container Bindings\n";
        $this->output .= "     * ======================================\n";
        $this->output .= "     */\n\n";

        $this->output .= "    /**\n";
        $this->output .= "     * Service Providers registered by this package:\n";
        foreach ($this->serviceProviders as $provider) {
            $this->output .= "     * - {$provider}\n";
        }
        $this->output .= "     */\n\n";
    }

    protected function parseParameters(string $params): array
    {
        if (empty(trim($params))) {
            return [];
        }

        $paramDocs = [];
        $paramList = explode(',', $params);

        foreach ($paramList as $param) {
            $param = trim($param);

            // Extract type hint and variable name
            if (preg_match('/^([\w\\\\]+)?\s*(\$\w+)/', $param, $matches)) {
                $type = $matches[1] ?? 'mixed';
                $var = $matches[2];
                $paramDocs[] = "{$type} {$var}";
            } elseif (preg_match('/(\$\w+)/', $param, $matches)) {
                $var = $matches[1];
                $paramDocs[] = "mixed {$var}";
            }
        }

        return $paramDocs;
    }

    protected function writeFile(): void
    {
        $outputFile = __DIR__ . '/_ide_helper.php';
        file_put_contents($outputFile, $this->output);
    }
}

// Run the generator
echo "╔════════════════════════════════════════════════════╗\n";
echo "║   Universal IDE Helper Generator                  ║\n";
echo "║   Package: vm-engine/synapse                      ║\n";
echo "╚════════════════════════════════════════════════════╝\n\n";

try {
    $generator = new IdeHelperGenerator();
    $generator->generate();

    echo "\n✓ Done! Add _ide_helper.php to your IDE for better autocompletion.\n";
    echo "  Don't forget to add it to .gitignore if not already present.\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
