<?php

use Illuminate\Support\Facades\File;
use VmEngine\Synapse\Services\SynapseUtils;

describe('SynapseUtils::copyDirectory()', function () {
    beforeEach(function () {
        /** @var stdClass $this */
        $this->tempSource = base_path('temp_test_source');
        $this->tempDest = base_path('temp_test_dest');

        // Clean up if exists
        if (is_dir($this->tempSource)) {
            File::deleteDirectory($this->tempSource);
        }
        if (is_dir($this->tempDest)) {
            File::deleteDirectory($this->tempDest);
        }
    });

    afterEach(function () {
        /** @var stdClass $this */
        // Clean up after tests
        if (is_dir($this->tempSource)) {
            File::deleteDirectory($this->tempSource);
        }
        if (is_dir($this->tempDest)) {
            File::deleteDirectory($this->tempDest);
        }
    });

    it('copies a simple directory', function () {
        /** @var stdClass $this */
        // Create source directory with a file
        mkdir($this->tempSource, 0777, true);
        file_put_contents($this->tempSource.'/test.txt', 'content');

        $result = SynapseUtils::copyDirectory($this->tempSource, $this->tempDest);

        expect($result)->toBeTrue()
            ->and(is_dir($this->tempDest))->toBeTrue()
            ->and(file_exists($this->tempDest.'/test.txt'))->toBeTrue()
            ->and(file_get_contents($this->tempDest.'/test.txt'))->toBe('content');
    });

    it('copies nested directories', function () {
        /** @var stdClass $this */
        // Create nested structure
        mkdir($this->tempSource.'/subdir', 0777, true);
        file_put_contents($this->tempSource.'/test.txt', 'root');
        file_put_contents($this->tempSource.'/subdir/nested.txt', 'nested');

        $result = SynapseUtils::copyDirectory($this->tempSource, $this->tempDest);

        expect($result)->toBeTrue()
            ->and(is_dir($this->tempDest.'/subdir'))->toBeTrue()
            ->and(file_exists($this->tempDest.'/test.txt'))->toBeTrue()
            ->and(file_exists($this->tempDest.'/subdir/nested.txt'))->toBeTrue();
    });

    it('handles trailing slashes', function () {
        /** @var stdClass $this */
        mkdir($this->tempSource, 0777, true);
        file_put_contents($this->tempSource.'/test.txt', 'content');

        $result = SynapseUtils::copyDirectory($this->tempSource.'/', $this->tempDest.'/');

        expect($result)->toBeTrue()
            ->and(file_exists($this->tempDest.'/test.txt'))->toBeTrue();
    });

    it('creates destination directory if not exists', function () {
        /** @var stdClass $this */
        mkdir($this->tempSource, 0777, true);
        file_put_contents($this->tempSource.'/test.txt', 'content');

        $result = SynapseUtils::copyDirectory($this->tempSource, $this->tempDest);

        expect($result)->toBeTrue()
            ->and(is_dir($this->tempDest))->toBeTrue();
    });

    it('copies empty directory', function () {
        /** @var stdClass $this */
        mkdir($this->tempSource, 0777, true);

        $result = SynapseUtils::copyDirectory($this->tempSource, $this->tempDest);

        expect($result)->toBeTrue()
            ->and(is_dir($this->tempDest))->toBeTrue();
    });

    it('copies multiple files', function () {
        /** @var stdClass $this */
        mkdir($this->tempSource, 0777, true);
        file_put_contents($this->tempSource.'/file1.txt', 'content1');
        file_put_contents($this->tempSource.'/file2.txt', 'content2');
        file_put_contents($this->tempSource.'/file3.txt', 'content3');

        $result = SynapseUtils::copyDirectory($this->tempSource, $this->tempDest);

        expect($result)->toBeTrue()
            ->and(file_exists($this->tempDest.'/file1.txt'))->toBeTrue()
            ->and(file_exists($this->tempDest.'/file2.txt'))->toBeTrue()
            ->and(file_exists($this->tempDest.'/file3.txt'))->toBeTrue();
    });
});

describe('SynapseUtils::isSynAppsAutoloaded()', function () {
    it('returns boolean', function () {
        $result = SynapseUtils::isSynAppsAutoloaded();

        expect($result)->toBeIn([true, false]);
    });

    it('checks for SynApps in composer.json', function () {
        // This test depends on the actual composer.json content
        $composerJson = file_get_contents(base_path('composer.json'));
        $hasSynApps = strpos($composerJson, 'SynApps') !== false;

        $result = SynapseUtils::isSynAppsAutoloaded();

        expect($result)->toBe($hasSynApps);
    });
});

describe('SynapseUtils::listModuleNamespaces()', function () {
    beforeEach(function () {
        /** @var stdClass $this */
        $this->testModulesPath = base_path('test_modules');

        // Clean up if exists
        if (is_dir($this->testModulesPath)) {
            File::deleteDirectory($this->testModulesPath);
        }

        // Create test modules
        mkdir($this->testModulesPath.'/test_module', 0777, true);
        mkdir($this->testModulesPath.'/another_module', 0777, true);
    });

    afterEach(function () {
        /** @var stdClass $this */
        if (is_dir($this->testModulesPath)) {
            File::deleteDirectory($this->testModulesPath);
        }
    });

    it('lists modules without prefix', function () {
        /** @var stdClass $this */
        $namespaces = [
            'TestNamespace' => [$this->testModulesPath, false],
        ];

        $modules = SynapseUtils::listModuleNamespaces($namespaces, function ($ns, $path, $name) {
            return $name;
        });

        expect($modules)->toBeArray()
            ->and($modules)->toContain('test_module')
            ->and($modules)->toContain('another_module');
    });

    it('lists modules with prefix', function () {
        /** @var stdClass $this */
        // Create modules with prefix
        mkdir($this->testModulesPath.'/synmod-test', 0777, true);
        mkdir($this->testModulesPath.'/synmod-another', 0777, true);

        $namespaces = [
            'TestNamespace' => [$this->testModulesPath, 'synmod-'],
        ];

        $modules = SynapseUtils::listModuleNamespaces($namespaces, function ($ns, $path, $name) {
            return $name;
        });

        expect($modules)->toBeArray()
            ->and($modules)->toContain('test')
            ->and($modules)->toContain('another');
    });

    it('converts module names to snake_case', function () {
        /** @var stdClass $this */
        mkdir($this->testModulesPath.'/TestModule', 0777, true);

        $namespaces = [
            'TestNamespace' => [$this->testModulesPath, false],
        ];

        $modules = SynapseUtils::listModuleNamespaces($namespaces, function ($ns, $path, $name) {
            return $name;
        });

        expect($modules)->toContain('test_module');
    });

    it('handles multiple prefixes', function () {
        /** @var stdClass $this */
        mkdir($this->testModulesPath.'/synmod-test', 0777, true);
        mkdir($this->testModulesPath.'/synmodule-another', 0777, true);

        $namespaces = [
            'TestNamespace' => [$this->testModulesPath, ['synmod-', 'synmodule-']],
        ];

        $modules = SynapseUtils::listModuleNamespaces($namespaces, function ($ns, $path, $name) {
            return $name;
        });

        expect($modules)->toBeArray()
            ->and(count($modules))->toBeGreaterThanOrEqual(2);
    });
});

describe('SynapseUtils::listModuleResources()', function () {
    beforeEach(function () {
        /** @var stdClass $this */
        $this->testModulesPath = base_path('test_modules');

        // Clean up if exists
        if (is_dir($this->testModulesPath)) {
            File::deleteDirectory($this->testModulesPath);
        }

        // Create test modules with resources
        mkdir($this->testModulesPath.'/test_module/database/migrations', 0777, true);
        mkdir($this->testModulesPath.'/another_module/database/migrations', 0777, true);
        mkdir($this->testModulesPath.'/no_migrations', 0777, true);
    });

    afterEach(function () {
        /** @var stdClass $this */
        if (is_dir($this->testModulesPath)) {
            File::deleteDirectory($this->testModulesPath);
        }
    });

    it('lists module resources that exist', function () {
        /** @var stdClass $this */
        $namespaces = [
            'TestNamespace' => [$this->testModulesPath, false],
        ];

        $resources = SynapseUtils::listModuleResources($namespaces, 'database/migrations');

        expect($resources)->toBeArray()
            ->and(count($resources))->toBe(2);
    });

    it('skips modules without the resource path', function () {
        /** @var stdClass $this */
        $namespaces = [
            'TestNamespace' => [$this->testModulesPath, false],
        ];

        $resources = SynapseUtils::listModuleResources($namespaces, 'database/migrations');

        // Should only include test_module and another_module, not no_migrations
        expect(count($resources))->toBe(2);
    });

    it('uses custom callback when provided', function () {
        /** @var stdClass $this */
        $namespaces = [
            'TestNamespace' => [$this->testModulesPath, false],
        ];

        $resources = SynapseUtils::listModuleResources(
            $namespaces,
            'database/migrations',
            function ($ns, $path, $name) {
                return ['namespace' => $ns, 'path' => $path, 'module' => $name];
            }
        );

        expect($resources)->toBeArray()
            ->and($resources[0])->toHaveKey('namespace')
            ->and($resources[0])->toHaveKey('path')
            ->and($resources[0])->toHaveKey('module');
    });

    it('returns paths by default when no callback provided', function () {
        /** @var stdClass $this */
        $namespaces = [
            'TestNamespace' => [$this->testModulesPath, false],
        ];

        $resources = SynapseUtils::listModuleResources($namespaces, 'database/migrations');

        expect($resources)->toBeArray()
            ->and($resources[0])->toBeString()
            ->and($resources[0])->toContain('database/migrations');
    });
});
