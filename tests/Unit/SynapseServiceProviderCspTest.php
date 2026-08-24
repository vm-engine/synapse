<?php

declare(strict_types=1);

use VmEngine\Synapse\SynapseServiceProvider;

function invokeOverrideLivewireCsp(): void
{
    $provider = new SynapseServiceProvider(app());
    $method = (new ReflectionClass($provider))->getMethod('overrideLivewireCsp');
    $method->setAccessible(true);
    $method->invoke($provider);
}

describe('livewire.csp_safe override', function () {
    it('forces csp_safe to true regardless of the published config default', function () {
        config(['livewire.csp_safe' => false]);

        invokeOverrideLivewireCsp();

        expect(config('livewire.csp_safe'))->toBeTrue();
    });

    it('forces csp_safe to true even when config/livewire.php was never published', function () {
        config()->offsetUnset('livewire.csp_safe');

        invokeOverrideLivewireCsp();

        expect(config('livewire.csp_safe'))->toBeTrue();
    });
});
