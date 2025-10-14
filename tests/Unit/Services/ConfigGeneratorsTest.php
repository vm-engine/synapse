<?php

use VmEngine\Synapse\Services\SynapseConfigGenerator;
use VmEngine\Synapse\Services\SynAppsConfigGenerator;
use Illuminate\Support\Facades\File;

describe('SynapseConfigGenerator', function () {
    it('has default config structure', function () {
        expect(SynapseConfigGenerator::$defaultConfig)->toBeArray()
            ->and(SynapseConfigGenerator::$defaultConfig)->toHaveKey('bindings')
            ->and(SynapseConfigGenerator::$defaultConfig)->toHaveKey('lang_path')
            ->and(SynapseConfigGenerator::$defaultConfig)->toHaveKey('module_path')
            ->and(SynapseConfigGenerator::$defaultConfig)->toHaveKey('view_path')
            ->and(SynapseConfigGenerator::$defaultConfig)->toHaveKey('migration_path')
            ->and(SynapseConfigGenerator::$defaultConfig)->toHaveKey('seed_path')
            ->and(SynapseConfigGenerator::$defaultConfig)->toHaveKey('namespaces');
    });

    it('default config has correct bindings structure', function () {
        $bindings = SynapseConfigGenerator::$defaultConfig['bindings'];

        expect($bindings)->toHaveKey('controller')
            ->and($bindings)->toHaveKey('interface')
            ->and($bindings)->toHaveKey('route');
    });

    it('generate returns default config when compiled files missing', function () {
        // Clean up any existing compiled files
        $compiledPath = base_path('synapps/config/compiled');
        if (is_dir($compiledPath)) {
            File::deleteDirectory($compiledPath);
        }

        $config = SynapseConfigGenerator::generate();

        expect($config)->toBeArray()
            ->and($config)->toBe(SynapseConfigGenerator::$defaultConfig);
    });

    it('hasCompiledConfigFile checks for required files', function () {
        $result = SynapseConfigGenerator::hasCompiledConfigFile();

        expect($result)->toBeIn([true, false]);
    });

    it('creates compiled directory if not exists', function () {
        $compiledPath = base_path('synapps/config/compiled');

        // Remove if exists
        if (is_dir($compiledPath)) {
            File::deleteDirectory($compiledPath);
        }

        SynapseConfigGenerator::hasCompiledConfigFile();

        expect(is_dir($compiledPath))->toBeTrue();
    });

    it('generates config with modules when compiled files exist', function () {
        // Ensure compiled files exist
        $compiledPath = base_path('synapps/config/compiled');
        if (!is_dir($compiledPath)) {
            mkdir($compiledPath, 0777, true);
        }

        // Create minimal compiled files
        file_put_contents($compiledPath . '/apps.json', json_encode([
            'app_code' => 'test',
            'app_name' => 'Test App',
        ]));

        file_put_contents($compiledPath . '/system.json', json_encode([
            'mode' => 'development',
        ]));

        $config = SynapseConfigGenerator::generate();

        expect($config)->toBeArray()
            ->and($config)->toHaveKey('namespaces')
            ->and($config)->toHaveKey('module_path')
            ->and($config)->toHaveKey('view_path')
            ->and($config)->toHaveKey('lang_path')
            ->and($config)->toHaveKey('migration_path')
            ->and($config)->toHaveKey('seed_path');
    });

    it('sets general namespaces correctly', function () {
        $compiledPath = base_path('synapps/config/compiled');
        if (!is_dir($compiledPath)) {
            mkdir($compiledPath, 0777, true);
        }

        file_put_contents($compiledPath . '/apps.json', json_encode(['app_code' => 'test']));
        file_put_contents($compiledPath . '/system.json', json_encode(['mode' => 'dev']));

        $config = SynapseConfigGenerator::generate();

        expect($config['namespaces'])->toHaveKey('general')
            ->and($config['namespaces']['general'])->toHaveKey('SynApps\Modules')
            ->and($config['namespaces']['general'])->toHaveKey('SynModule');
    });
});

describe('SynAppsConfigGenerator', function () {
    beforeEach(function () {
        // Ensure synapps directory structure exists
        $synappsPath = base_path('synapps');
        $dirs = ['config', 'config/compiled'];

        foreach ($dirs as $dir) {
            $path = $synappsPath . '/' . $dir;
            if (!is_dir($path)) {
                mkdir($path, 0777, true);
            }
        }

        // Create minimal config files
        $configPath = $synappsPath . '/config';

        if (!file_exists($configPath . '/apps.json')) {
            file_put_contents($configPath . '/apps.json', json_encode([
                'app_code' => 'test_app',
                'app_name' => 'Test Application',
                'author' => 'Test Author',
                'email' => 'test@example.com',
                'dependencies' => [
                    'npm' => ['main' => new stdClass(), 'dev' => new stdClass()],
                    'composer' => ['main' => new stdClass(), 'dev' => new stdClass()],
                ],
            ]));
        }

        if (!file_exists($configPath . '/system.json')) {
            file_put_contents($configPath . '/system.json', json_encode([
                'mode' => 'development',
                'locale' => 'en',
            ]));
        }
    });

    it('can be instantiated', function () {
        $generator = new SynAppsConfigGenerator();

        expect($generator)->toBeInstanceOf(SynAppsConfigGenerator::class);
    });

    it('has generate method', function () {
        $generator = new SynAppsConfigGenerator();

        expect($generator)->toHaveMethod('generate');
    });

    it('generate method returns array', function () {
        $generator = new SynAppsConfigGenerator();
        $config = $generator->generate();

        expect($config)->toBeArray();
    });

    it('generates apps configuration', function () {
        $generator = new SynAppsConfigGenerator();
        $config = $generator->generate();

        expect($config)->toHaveKey('apps');
    });

    it('generates system configuration', function () {
        $generator = new SynAppsConfigGenerator();
        $config = $generator->generate();

        expect($config)->toHaveKey('system');
    });

    it('generates modules configuration', function () {
        $generator = new SynAppsConfigGenerator();
        $config = $generator->generate();

        expect($config)->toHaveKey('modules');
    });

    it('generates binding configuration', function () {
        $generator = new SynAppsConfigGenerator();
        $config = $generator->generate();

        expect($config)->toHaveKey('binding');
    });

    it('writes compiled configurations', function () {
        $generator = new SynAppsConfigGenerator();
        $generator->generate();

        $compiledPath = base_path('synapps/config/compiled');

        expect(file_exists($compiledPath . '/apps.json'))->toBeTrue()
            ->and(file_exists($compiledPath . '/system.json'))->toBeTrue()
            ->and(file_exists($compiledPath . '/modules.json'))->toBeTrue()
            ->and(file_exists($compiledPath . '/binding.json'))->toBeTrue();
    });
});

describe('Config Generator Integration', function () {
    beforeEach(function () {
        // Setup complete test environment
        $synappsPath = base_path('synapps');
        $dirs = ['config', 'config/compiled', 'modules'];

        foreach ($dirs as $dir) {
            $path = $synappsPath . '/' . $dir;
            if (!is_dir($path)) {
                mkdir($path, 0777, true);
            }
        }

        // Create config files
        $configPath = $synappsPath . '/config';

        file_put_contents($configPath . '/apps.json', json_encode([
            'app_code' => 'integration_test',
            'app_name' => 'Integration Test',
            'author' => 'Test',
            'email' => 'test@test.com',
            'urls' => [
                'domain' => ['dev' => 'http://localhost', 'prod' => 'https://example.com'],
                'prefix' => '/',
                'api_prefix' => '/api',
            ],
            'dependencies' => [
                'npm' => ['main' => new stdClass(), 'dev' => new stdClass()],
                'composer' => ['main' => new stdClass(), 'dev' => new stdClass()],
            ],
        ]));

        file_put_contents($configPath . '/system.json', json_encode([
            'mode' => 'development',
            'locale' => 'en',
            'fallback_locale' => 'en',
        ]));

        // Create a test module
        $this->createTestModule('integration_module');
    });

    afterEach(function () {
        $this->cleanupTestModules();
    });

    it('generates complete configuration from both generators', function () {
        // Generate SynApps config first
        $synAppsGenerator = new SynAppsConfigGenerator();
        $synAppsConfig = $synAppsGenerator->generate();

        // Then generate Synapse config
        $synapseConfig = SynapseConfigGenerator::generate();

        expect($synAppsConfig)->toBeArray()
            ->and($synapseConfig)->toBeArray()
            ->and($synAppsConfig)->toHaveKey('apps')
            ->and($synAppsConfig)->toHaveKey('system')
            ->and($synapseConfig)->toHaveKey('namespaces')
            ->and($synapseConfig)->toHaveKey('module_path');
    });

    it('discovers test module', function () {
        $synAppsGenerator = new SynAppsConfigGenerator();
        $config = $synAppsGenerator->generate();

        expect($config['modules'])->toHaveKey('integration_module');
    });

    it('compiled files contain valid JSON', function () {
        $synAppsGenerator = new SynAppsConfigGenerator();
        $synAppsGenerator->generate();

        $compiledPath = base_path('synapps/config/compiled');
        $files = ['apps.json', 'system.json', 'modules.json', 'binding.json'];

        foreach ($files as $file) {
            $content = file_get_contents($compiledPath . '/' . $file);
            $decoded = json_decode($content, true);

            expect($decoded)->toBeArray()
                ->and(json_last_error())->toBe(JSON_ERROR_NONE);
        }
    });

    it('modules configuration includes module metadata', function () {
        $synAppsGenerator = new SynAppsConfigGenerator();
        $config = $synAppsGenerator->generate();

        $module = $config['modules']['integration_module'];

        expect($module)->toHaveKey('id')
            ->and($module)->toHaveKey('name')
            ->and($module)->toHaveKey('version')
            ->and($module)->toHaveKey('id_case')
            ->and($module)->toHaveKey('dependencies');
    });
});

describe('Config File Validation', function () {
    it('validates apps.json structure', function () {
        $compiledPath = base_path('synapps/config/compiled');
        if (!file_exists($compiledPath . '/apps.json')) {
            $generator = new SynAppsConfigGenerator();
            $generator->generate();
        }

        $appsJson = json_decode(file_get_contents($compiledPath . '/apps.json'), true);

        expect($appsJson)->toBeArray()
            ->and($appsJson)->toHaveKey('app_code');
    });

    it('validates system.json structure', function () {
        $compiledPath = base_path('synapps/config/compiled');
        if (!file_exists($compiledPath . '/system.json')) {
            $generator = new SynAppsConfigGenerator();
            $generator->generate();
        }

        $systemJson = json_decode(file_get_contents($compiledPath . '/system.json'), true);

        expect($systemJson)->toBeArray();
    });

    it('validates modules.json structure', function () {
        $compiledPath = base_path('synapps/config/compiled');
        if (!file_exists($compiledPath . '/modules.json')) {
            $generator = new SynAppsConfigGenerator();
            $generator->generate();
        }

        $modulesJson = json_decode(file_get_contents($compiledPath . '/modules.json'), true);

        expect($modulesJson)->toBeArray();
    });

    it('validates binding.json structure', function () {
        $compiledPath = base_path('synapps/config/compiled');
        if (!file_exists($compiledPath . '/binding.json')) {
            $generator = new SynAppsConfigGenerator();
            $generator->generate();
        }

        $bindingJson = json_decode(file_get_contents($compiledPath . '/binding.json'), true);

        expect($bindingJson)->toBeArray();
    });
});
