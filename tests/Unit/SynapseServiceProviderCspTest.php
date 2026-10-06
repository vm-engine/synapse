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

describe('Livewire CSP $wire assignment workaround', function () {
    it('keeps the component.init $wire wrapper in the synapse-components.js stub', function () {
        // Without it, every #[Modelable] child (x-modelable="$wire.value") throws
        // "properties[name] is not a function" under the CSP build and parent->child
        // value pushes are lost. Remove only once Livewire's $wire proxy stops using
        // `property in aliases` (still the case in livewire/livewire v4.4.7).
        $js = (string) file_get_contents(dirname(__DIR__, 2).'/stubs/synapps/resources/js/synapse-components.js');

        expect($js)->toContain('Livewire.hook("component.init"')
            ->toContain('property in Object.prototype')
            ->toContain('component.$wire = component.el.$wire = new Proxy(wire');
    });
});
