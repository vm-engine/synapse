<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use VmEngine\Synapse\Components\ColorPicker;

describe('ColorPicker Component', function () {
    beforeEach(function () {
        view()->addNamespace('synapse', __DIR__.'/../../../resources/views');
        Blade::component(ColorPicker::class, 'synapse-color-picker');
    });

    it('keeps only #rrggbb swatches', function () {
        $component = new ColorPicker(swatches: ['#ef4444', 'red', '#12345', '#ABCDEF', 5, '#ef4444;']);

        expect($component->swatches)->toBe(['#ef4444', '#ABCDEF']);
    });

    it('forces the custom inputs on when there are no swatches', function () {
        $component = new ColorPicker(allowCustom: false);

        expect($component->allowCustom)->toBeTrue();
    });

    it('entangles the wire:model property and renders swatch buttons', function () {
        $html = Blade::render('<x-synapse-color-picker wire:model="form.color" :swatches="[\'#ef4444\']" />');

        expect($html)->toContain("synapseColorPicker(\$wire.entangle('form.color'))")
            ->toContain('type="color"')
            ->toContain("pick('#ef4444')")
            ->toContain("isSelected('#ef4444')")
            ->not->toContain('wire:model="form.color"');
    });

    it('entangles live when wire:model.live is used', function () {
        $html = Blade::render('<x-synapse-color-picker wire:model.live="color" />');

        expect($html)->toContain("synapseColorPicker(\$wire.entangle('color').live)");
    });

    it('does not emit an empty entangle without wire:model', function () {
        $html = Blade::render('<x-synapse-color-picker />');

        expect($html)->toContain('synapseColorPicker(null)')
            ->not->toContain("entangle('')");
    });

    it('hides the custom inputs when allowCustom is false and swatches exist', function () {
        $html = Blade::render('<x-synapse-color-picker wire:model="color" :allow-custom="false" :swatches="[\'#3b82f6\']" />');

        expect($html)->not->toContain('type="color"')
            ->toContain("pick('#3b82f6')");
    });
});
