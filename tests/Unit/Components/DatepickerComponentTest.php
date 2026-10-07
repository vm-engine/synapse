<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use VmEngine\Synapse\Components\Datepicker;

describe('Datepicker entangle', function () {
    beforeEach(function () {
        view()->addNamespace('synapse', __DIR__.'/../../../resources/views');
        Blade::component(Datepicker::class, 'synapse-datepicker');
    });

    it('entangles deferred by default', function () {
        $html = Blade::render('<x-synapse-datepicker wire:model="dueAt" />');

        expect($html)->toContain("synapseDatepicker(\$wire.entangle('dueAt'), {})");
    });

    it('entangles live when wire:model.live is used', function () {
        $html = Blade::render('<x-synapse-datepicker wire:model.live="dueFrom" />');

        expect($html)->toContain("synapseDatepicker(\$wire.entangle('dueFrom').live, {})");
    });

    it('does not emit an empty entangle without wire:model', function () {
        $html = Blade::render('<x-synapse-datepicker />');

        expect($html)->toContain('synapseDatepicker(null, {})')
            ->not->toContain("entangle('')");
    });
});
