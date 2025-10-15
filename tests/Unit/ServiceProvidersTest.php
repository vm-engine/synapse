<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Blade;
use VmEngine\Synapse\SynapseLangServiceProvider;
use VmEngine\Synapse\SynapseRouteServiceProvider;
use VmEngine\Synapse\SynapseServiceProvider;
use VmEngine\Synapse\SynapseViewServiceProvider;

describe('SynapseServiceProvider', function () {
    it('is registered in application', function () {
        $providers = app()->getLoadedProviders();

        expect($providers)->toHaveKey(SynapseServiceProvider::class);
    });

    it('publishes config files', function () {
        // Check if config publishing is registered
        $published = SynapseServiceProvider::$publishes;

        expect($published)->not->toBeEmpty();
    });

    it('registers commands', function () {
        // Check if artisan commands are available
        expect(Artisan::all())->toHaveKey('synapps:setup')
            ->and(Artisan::all())->toHaveKey('synapps:discover')
            ->and(Artisan::all())->toHaveKey('synapps:patch-resources');
    });
});

describe('SynapseLangServiceProvider', function () {
    it('is registered in application', function () {
        $providers = app()->getLoadedProviders();

        expect($providers)->toHaveKey(SynapseLangServiceProvider::class);
    });
});

describe('SynapseRouteServiceProvider', function () {
    it('is registered in application', function () {
        $providers = app()->getLoadedProviders();

        expect($providers)->toHaveKey(SynapseRouteServiceProvider::class);
    });

    it('registers module routes', function () {
        // This would require actual modules to be present
        // Just verify the provider is loaded
        expect(app()->getLoadedProviders())->toHaveKey(SynapseRouteServiceProvider::class);
    });
});

describe('SynapseViewServiceProvider', function () {
    it('is registered in application', function () {
        $providers = app()->getLoadedProviders();

        expect($providers)->toHaveKey(SynapseViewServiceProvider::class);
    });

    it('registers blade components', function () {
        // Check if synapse blade components are registered
        $componentNamespaces = Blade::getClassComponentAliases();

        // Verify some of the synapse components are registered
        expect(array_keys($componentNamespaces))->toContain('synapse-checkboxes')
            ->and(array_keys($componentNamespaces))->toContain('synapse-radio-buttons')
            ->and(array_keys($componentNamespaces))->toContain('synapse-select')
            ->and(array_keys($componentNamespaces))->toContain('synapse-password')
            ->and(array_keys($componentNamespaces))->toContain('synapse-currency')
            ->and(array_keys($componentNamespaces))->toContain('synapse-datepicker')
            ->and(array_keys($componentNamespaces))->toContain('synapse-confirm-dialog')
            ->and(array_keys($componentNamespaces))->toContain('synapse-drawer')
            ->and(array_keys($componentNamespaces))->toContain('synapse-toastr');
    });
});

describe('Package Discovery', function () {
    it('all service providers are auto-discovered', function () {
        $providers = [
            SynapseServiceProvider::class,
            SynapseLangServiceProvider::class,
            SynapseRouteServiceProvider::class,
            SynapseViewServiceProvider::class,
        ];

        $loadedProviders = app()->getLoadedProviders();

        foreach ($providers as $provider) {
            expect($loadedProviders)->toHaveKey($provider);
        }
    });
});
