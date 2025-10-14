<?php

namespace Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use VmEngine\Synapse\SynapseServiceProvider;
use VmEngine\Synapse\SynapseLangServiceProvider;
use VmEngine\Synapse\SynapseRouteServiceProvider;
use VmEngine\Synapse\SynapseViewServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // Additional setup
    }

    protected function getPackageProviders($app)
    {
        return [
            SynapseServiceProvider::class,
            SynapseLangServiceProvider::class,
            SynapseRouteServiceProvider::class,
            SynapseViewServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Create synapps directory structure for testing
        $synappsPath = base_path('synapps');
        if (!is_dir($synappsPath)) {
            mkdir($synappsPath, 0777, true);
        }

        $dirs = [
            'modules',
            'config',
            'config/compiled',
            'resources',
        ];

        foreach ($dirs as $dir) {
            $path = $synappsPath . '/' . $dir;
            if (!is_dir($path)) {
                mkdir($path, 0777, true);
            }
        }
    }

    protected function defineDatabaseMigrations()
    {
        // Define database migrations if needed
    }

    /**
     * Create a test module directory structure
     */
    protected function createTestModule(string $moduleName, array $moduleConfig = []): string
    {
        $modulePath = base_path("synapps/modules/{$moduleName}");

        if (!is_dir($modulePath)) {
            mkdir($modulePath, 0777, true);
        }

        // Create basic structure
        $dirs = ['src', 'routes', 'resources/views', 'database/migrations', 'database/seeders', 'database/factories'];
        foreach ($dirs as $dir) {
            $path = $modulePath . '/' . $dir;
            if (!is_dir($path)) {
                mkdir($path, 0777, true);
            }
        }

        // Create module.json
        $defaultConfig = [
            'id' => $moduleName,
            'id_case' => [
                'snake' => $moduleName,
                'pascal' => str_replace('_', '', ucwords($moduleName, '_')),
                'slug' => str_replace('_', '-', $moduleName),
                'studly' => lcfirst(str_replace('_', '', ucwords($moduleName, '_'))),
            ],
            'name' => ucwords(str_replace('_', ' ', $moduleName)),
            'description' => 'Test module',
            'version' => '0.1.0',
            'author' => 'Test Author',
            'email' => 'test@example.com',
            'url_prefix' => $moduleName,
            'load_priority' => 99,
            'dependencies' => [
                'npm' => ['main' => new \stdClass(), 'dev' => new \stdClass()],
                'composer' => ['main' => new \stdClass(), 'dev' => new \stdClass()],
            ],
        ];

        $config = array_merge($defaultConfig, $moduleConfig);
        file_put_contents($modulePath . '/module.json', json_encode($config, JSON_PRETTY_PRINT));

        return $modulePath;
    }

    /**
     * Clean up test modules
     */
    protected function cleanupTestModules(): void
    {
        $modulesPath = base_path('synapps/modules');
        if (is_dir($modulesPath)) {
            $this->deleteDirectory($modulesPath);
        }
    }

    /**
     * Recursively delete a directory
     */
    protected function deleteDirectory(string $dir): bool
    {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }

        return rmdir($dir);
    }
}
