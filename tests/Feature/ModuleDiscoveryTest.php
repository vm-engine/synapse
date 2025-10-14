<?php

use Illuminate\Support\Facades\File;

describe('Module Discovery Integration', function () {
    beforeEach(function () {
        // Create test module structure
        $this->modulePath = $this->createTestModule('integration_test', [
            'name' => 'Integration Test Module',
            'version' => '1.0.0',
            'dependencies' => [
                'npm' => [
                    'main' => ['test-package' => '^1.0'],
                    'dev' => new \stdClass(),
                ],
                'composer' => [
                    'main' => new \stdClass(),
                    'dev' => new \stdClass(),
                ],
            ],
        ]);
    });

    afterEach(function () {
        $this->cleanupTestModules();
    });

    it('creates module directory structure', function () {
        expect(is_dir($this->modulePath))->toBeTrue()
            ->and(is_dir($this->modulePath . '/src'))->toBeTrue()
            ->and(is_dir($this->modulePath . '/routes'))->toBeTrue()
            ->and(is_dir($this->modulePath . '/resources/views'))->toBeTrue()
            ->and(is_dir($this->modulePath . '/database/migrations'))->toBeTrue()
            ->and(is_dir($this->modulePath . '/database/seeders'))->toBeTrue()
            ->and(is_dir($this->modulePath . '/database/factories'))->toBeTrue();
    });

    it('creates valid module.json', function () {
        $moduleJsonPath = $this->modulePath . '/module.json';

        expect(file_exists($moduleJsonPath))->toBeTrue();

        $config = json_decode(file_get_contents($moduleJsonPath), true);

        expect($config)->toBeArray()
            ->and($config)->toHaveKey('id')
            ->and($config)->toHaveKey('name')
            ->and($config)->toHaveKey('version')
            ->and($config)->toHaveKey('id_case')
            ->and($config)->toHaveKey('dependencies');
    });

    it('module.json has correct id_case structure', function () {
        $moduleJsonPath = $this->modulePath . '/module.json';
        $config = json_decode(file_get_contents($moduleJsonPath), true);

        expect($config['id_case'])->toHaveKey('snake')
            ->and($config['id_case'])->toHaveKey('pascal')
            ->and($config['id_case'])->toHaveKey('slug')
            ->and($config['id_case'])->toHaveKey('studly');
    });

    it('module.json has correct dependencies structure', function () {
        $moduleJsonPath = $this->modulePath . '/module.json';
        $config = json_decode(file_get_contents($moduleJsonPath), true);

        expect($config['dependencies'])->toHaveKey('npm')
            ->and($config['dependencies'])->toHaveKey('composer')
            ->and($config['dependencies']['npm'])->toHaveKey('main')
            ->and($config['dependencies']['npm'])->toHaveKey('dev')
            ->and($config['dependencies']['composer'])->toHaveKey('main')
            ->and($config['dependencies']['composer'])->toHaveKey('dev');
    });

    it('can create multiple modules', function () {
        $module1 = $this->createTestModule('module_one');
        $module2 = $this->createTestModule('module_two');

        expect(is_dir($module1))->toBeTrue()
            ->and(is_dir($module2))->toBeTrue()
            ->and($module1)->not->toBe($module2);
    });

    it('modules have unique configurations', function () {
        $module1 = $this->createTestModule('module_one', ['version' => '1.0.0']);
        $module2 = $this->createTestModule('module_two', ['version' => '2.0.0']);

        $config1 = json_decode(file_get_contents($module1 . '/module.json'), true);
        $config2 = json_decode(file_get_contents($module2 . '/module.json'), true);

        expect($config1['id'])->toBe('module_one')
            ->and($config2['id'])->toBe('module_two')
            ->and($config1['version'])->toBe('1.0.0')
            ->and($config2['version'])->toBe('2.0.0');
    });
});

describe('Module Configuration Validation', function () {
    it('validates required module.json fields', function () {
        $requiredFields = [
            'id',
            'id_case',
            'name',
            'description',
            'version',
            'author',
            'email',
            'url_prefix',
            'load_priority',
            'dependencies',
        ];

        $modulePath = $this->createTestModule('validation_test');
        $config = json_decode(file_get_contents($modulePath . '/module.json'), true);

        foreach ($requiredFields as $field) {
            expect($config)->toHaveKey($field);
        }

        $this->cleanupTestModules();
    });

    it('validates id_case has all required formats', function () {
        $requiredFormats = ['snake', 'pascal', 'slug', 'studly'];

        $modulePath = $this->createTestModule('case_test');
        $config = json_decode(file_get_contents($modulePath . '/module.json'), true);

        foreach ($requiredFormats as $format) {
            expect($config['id_case'])->toHaveKey($format);
        }

        $this->cleanupTestModules();
    });

    it('validates dependencies structure', function () {
        $modulePath = $this->createTestModule('deps_test');
        $config = json_decode(file_get_contents($modulePath . '/module.json'), true);

        expect($config['dependencies'])->toHaveKey('npm')
            ->and($config['dependencies'])->toHaveKey('composer')
            ->and($config['dependencies']['npm'])->toHaveKey('main')
            ->and($config['dependencies']['npm'])->toHaveKey('dev')
            ->and($config['dependencies']['composer'])->toHaveKey('main')
            ->and($config['dependencies']['composer'])->toHaveKey('dev');

        $this->cleanupTestModules();
    });
});

describe('Module Resource Structure', function () {
    it('creates all required directories', function () {
        $modulePath = $this->createTestModule('resource_test');

        $requiredDirs = [
            'src',
            'routes',
            'resources/views',
            'database/migrations',
            'database/seeders',
            'database/factories',
        ];

        foreach ($requiredDirs as $dir) {
            expect(is_dir($modulePath . '/' . $dir))->toBeTrue();
        }

        $this->cleanupTestModules();
    });

    it('directories are writable', function () {
        $modulePath = $this->createTestModule('writable_test');

        $testDirs = ['src', 'routes', 'resources/views'];

        foreach ($testDirs as $dir) {
            $fullPath = $modulePath . '/' . $dir;
            expect(is_writable($fullPath))->toBeTrue();
        }

        $this->cleanupTestModules();
    });

    it('can write files to module directories', function () {
        $modulePath = $this->createTestModule('file_write_test');

        // Try writing a test file
        $testFile = $modulePath . '/src/TestClass.php';
        $content = '<?php class TestClass {}';
        file_put_contents($testFile, $content);

        expect(file_exists($testFile))->toBeTrue()
            ->and(file_get_contents($testFile))->toBe($content);

        $this->cleanupTestModules();
    });
});

describe('Module Naming Conventions', function () {
    it('handles snake_case module names', function () {
        $modulePath = $this->createTestModule('test_module_name');
        $config = json_decode(file_get_contents($modulePath . '/module.json'), true);

        expect($config['id'])->toBe('test_module_name')
            ->and($config['id_case']['snake'])->toBe('test_module_name');

        $this->cleanupTestModules();
    });

    it('converts to PascalCase correctly', function () {
        $modulePath = $this->createTestModule('test_module');
        $config = json_decode(file_get_contents($modulePath . '/module.json'), true);

        expect($config['id_case']['pascal'])->toBe('TestModule');

        $this->cleanupTestModules();
    });

    it('converts to slug format correctly', function () {
        $modulePath = $this->createTestModule('test_module');
        $config = json_decode(file_get_contents($modulePath . '/module.json'), true);

        expect($config['id_case']['slug'])->toBe('test-module');

        $this->cleanupTestModules();
    });

    it('converts to studly format correctly', function () {
        $modulePath = $this->createTestModule('test_module');
        $config = json_decode(file_get_contents($modulePath . '/module.json'), true);

        expect($config['id_case']['studly'])->toBe('testModule');

        $this->cleanupTestModules();
    });
});
