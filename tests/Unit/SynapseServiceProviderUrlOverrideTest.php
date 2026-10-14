<?php

declare(strict_types=1);

use VmEngine\Synapse\SynapseServiceProvider;

function invokeOverrideAppUrl(): void
{
    $provider = new SynapseServiceProvider(app());
    $method = (new ReflectionClass($provider))->getMethod('overrideAppUrl');
    $method->setAccessible(true);
    $method->invoke($provider);
}

describe('app.url override', function () {
    it('overrides app.url with the dev domain outside production', function () {
        config(['synapps.apps.urls.domain' => ['dev' => 'synapsev3.test', 'prod' => 'synapsev3.com']]);

        invokeOverrideAppUrl();

        expect(config('app.url'))->toBe('http://synapsev3.test');
    });

    it('overrides app.url with the prod domain in production', function () {
        app()->detectEnvironment(fn () => 'production');
        config(['synapps.apps.urls.domain' => ['dev' => 'synapsev3.test', 'prod' => 'synapsev3.com']]);

        invokeOverrideAppUrl();

        expect(config('app.url'))->toBe('https://synapsev3.com');

        app()->detectEnvironment(fn () => 'testing');
    });

    it('falls back to the original app.url when the domain is not configured', function () {
        config(['synapps.apps.urls.domain' => ['dev' => '', 'prod' => '']]);
        config(['app.url' => 'http://original-app-url']);

        invokeOverrideAppUrl();

        expect(config('app.url'))->toBe('http://original-app-url');
    });

    it('points console URL generation (queued mail, scheduler) at the overridden app.url', function () {
        config(['synapps.apps.urls.domain' => ['dev' => 'synapsev3.test', 'prod' => '']]);

        invokeOverrideAppUrl();

        expect(url('/'))->toBe('http://synapsev3.test')
            ->and(url('/crm/tickets/5'))->toBe('http://synapsev3.test/crm/tickets/5');
    });

    it('leaves console URL generation alone when no domain is configured', function () {
        config(['synapps.apps.urls.domain' => ['dev' => '', 'prod' => '']]);
        config(['app.url' => 'http://original-app-url']);

        invokeOverrideAppUrl();

        expect(url('/'))->toBe('http://original-app-url');
    });
});
