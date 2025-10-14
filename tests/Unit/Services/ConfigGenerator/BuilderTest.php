<?php

use VmEngine\Synapse\Services\ConfigGenerator\Builder;

describe('Builder', function () {
    beforeEach(function () {
        // Ensure synapps directory exists
        $synappsPath = base_path('synapps');
        if (!is_dir($synappsPath)) {
            mkdir($synappsPath, 0777, true);
        }

        $configPath = $synappsPath . '/config';
        if (!is_dir($configPath)) {
            mkdir($configPath, 0777, true);
        }

        // Create minimal config files if they don't exist
        $appsJsonPath = $configPath . '/apps.json';
        if (!file_exists($appsJsonPath)) {
            file_put_contents($appsJsonPath, json_encode([
                'app_code' => 'test',
                'app_name' => 'Test App',
                'dependencies' => [
                    'npm' => ['main' => new stdClass(), 'dev' => new stdClass()],
                    'composer' => ['main' => new stdClass(), 'dev' => new stdClass()],
                ],
            ]));
        }

        $systemJsonPath = $configPath . '/system.json';
        if (!file_exists($systemJsonPath)) {
            file_put_contents($systemJsonPath, json_encode([
                'mode' => 'development',
            ]));
        }

        $this->builder = new Builder();
    });

    it('can be instantiated', function () {
        expect($this->builder)->toBeInstanceOf(Builder::class);
    });

    it('has synappsPath property', function () {
        expect($this->builder->synappsPath)->toBeString()
            ->and($this->builder->synappsPath)->toContain('synapps');
    });

    it('has mainConfig property', function () {
        expect($this->builder->mainConfig)->toBeArray();
    });

    it('has config property', function () {
        expect($this->builder->config)->toBeArray();
    });

    it('has configHash property', function () {
        expect($this->builder->configHash)->toBeString()
            ->and(strlen($this->builder->configHash))->toBe(64); // SHA256 hash length
    });

    it('initializes config structure', function () {
        expect($this->builder->config)->toHaveKey('apps')
            ->and($this->builder->config)->toHaveKey('system')
            ->and($this->builder->config)->toHaveKey('binding')
            ->and($this->builder->config)->toHaveKey('modules');
    });

    it('generate method returns config', function () {
        $config = $this->builder->generate();

        expect($config)->toBeArray()
            ->and($config)->toHaveKey('apps')
            ->and($config)->toHaveKey('system')
            ->and($config)->toHaveKey('modules')
            ->and($config)->toHaveKey('binding');
    });

    it('loads config from config.json', function () {
        expect($this->builder->mainConfig)->toBeArray()
            ->and($this->builder->mainConfig)->not->toBeEmpty();
    });
});
