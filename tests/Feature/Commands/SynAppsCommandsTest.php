<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

describe('synapps:patch-resources command', function () {
    beforeEach(function () {
        // Create resources directories if they don't exist
        $resourcesPath = base_path('resources');
        if (!is_dir($resourcesPath)) {
            mkdir($resourcesPath, 0777, true);
        }

        if (!is_dir($resourcesPath . '/js')) {
            mkdir($resourcesPath . '/js', 0777, true);
        }

        if (!is_dir($resourcesPath . '/css')) {
            mkdir($resourcesPath . '/css', 0777, true);
        }

        // Create minimal app.js and app.css files
        file_put_contents($resourcesPath . '/js/app.js', "import './bootstrap.js';\n");
        file_put_contents($resourcesPath . '/css/app.css', "@import './bootstrap.css';\n");

        // Create synapps resources
        $synappsPath = base_path('synapps/resources');
        if (!is_dir($synappsPath . '/js')) {
            mkdir($synappsPath . '/js', 0777, true);
        }
        if (!is_dir($synappsPath . '/css')) {
            mkdir($synappsPath . '/css', 0777, true);
        }

        file_put_contents($synappsPath . '/js/synapps.js', "console.log('synapps');\n");
        file_put_contents($synappsPath . '/css/synapps.css', "body { margin: 0; }\n");
    });

    it('patches app.js with synapps import', function () {
        Artisan::call('synapps:patch-resources');

        $appJs = file_get_contents(base_path('resources/js/app.js'));

        expect($appJs)->toContain('synapps.js');
    });

    it('patches app.css with synapps import', function () {
        Artisan::call('synapps:patch-resources');

        $appCss = file_get_contents(base_path('resources/css/app.css'));

        expect($appCss)->toContain('synapps.css');
    });

    it('is idempotent - running twice does not duplicate imports', function () {
        Artisan::call('synapps:patch-resources');
        Artisan::call('synapps:patch-resources');

        $appJs = file_get_contents(base_path('resources/js/app.js'));
        $appCss = file_get_contents(base_path('resources/css/app.css'));

        // Count occurrences
        $jsCount = substr_count($appJs, 'synapps.js');
        $cssCount = substr_count($appCss, 'synapps.css');

        expect($jsCount)->toBe(1)
            ->and($cssCount)->toBe(1);
    });

    it('returns success message', function () {
        $result = Artisan::call('synapps:patch-resources');

        expect($result)->toBe(0); // Success exit code
    });
});

describe('synapps:discover command', function () {
    beforeEach(function () {
        // Ensure required directories exist
        $modulesPath = base_path('synapps/modules');
        if (!is_dir($modulesPath)) {
            mkdir($modulesPath, 0777, true);
        }

        // Create a test module
        $testModulePath = $modulesPath . '/test_module';
        if (!is_dir($testModulePath)) {
            mkdir($testModulePath, 0777, true);
        }

        // Create module.json
        $moduleConfig = [
            'id' => 'test_module',
            'id_case' => [
                'snake' => 'test_module',
                'pascal' => 'TestModule',
                'slug' => 'test-module',
                'studly' => 'testModule',
            ],
            'name' => 'Test Module',
            'description' => 'Test module description',
            'version' => '0.1.0',
            'author' => 'Test Author',
            'email' => 'test@example.com',
            'url_prefix' => 'test',
            'load_priority' => 99,
            'dependencies' => [
                'npm' => ['main' => new stdClass(), 'dev' => new stdClass()],
                'composer' => ['main' => new stdClass(), 'dev' => new stdClass()],
            ],
        ];

        file_put_contents(
            $testModulePath . '/module.json',
            json_encode($moduleConfig, JSON_PRETTY_PRINT)
        );
    });

    it('can discover modules', function () {
        // This test would require mocking composer and npm commands
        // For now, we just test that the command can be called
        expect(true)->toBeTrue();
    })->skip('Requires mocking composer and npm');

    it('adds PSR-4 namespaces to composer.json', function () {
        // This test would modify the actual composer.json
        // Should be tested in an isolated environment
        expect(true)->toBeTrue();
    })->skip('Requires isolated test environment');
});

describe('synapps:setup command', function () {
    it('creates synapps directory structure', function () {
        // This command is interactive and requires user input
        // Testing would require mocking Laravel Prompts
        expect(true)->toBeTrue();
    })->skip('Interactive command requires prompt mocking');

    it('copies stub files', function () {
        // This test would create actual files
        // Should be tested in isolated environment
        expect(true)->toBeTrue();
    })->skip('Requires isolated test environment');

    it('generates apps.json configuration', function () {
        // This test would create actual files
        // Should be tested in isolated environment
        expect(true)->toBeTrue();
    })->skip('Requires isolated test environment');
});
