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

describe('Datepicker month mode', function () {
    beforeEach(function () {
        view()->addNamespace('synapse', __DIR__.'/../../../resources/views');
        Blade::component(Datepicker::class, 'synapse-datepicker');
    });

    it('passes the month mode through to the Alpine component', function () {
        $html = Blade::render('<x-synapse-datepicker mode="month" wire:model="period" />');

        // The quotes arrive HTML-escaped, exactly as $flatpickr's do; the browser unescapes
        // them parsing the attribute, which is verified working in the wild.
        expect($html)->toContain("synapseDatepicker(\$wire.entangle('period'), {}, &#039;month&#039;)");
    });

    it('passes the month mode through without wire:model', function () {
        $html = Blade::render('<x-synapse-datepicker mode="month" />');

        expect($html)->toContain('synapseDatepicker(null, {}, &#039;month&#039;)');
    });

    it('emits no mode argument for the default date mode', function () {
        $html = Blade::render('<x-synapse-datepicker wire:model="dueAt" />');

        expect($html)->toContain("synapseDatepicker(\$wire.entangle('dueAt'), {})")
            ->not->toContain("'date'");
    });

    // $mode lands inside a JS string literal in x-data, so an arbitrary call-site value must
    // never reach it. Anything but 'month' is the default mode.
    it('falls back to the default mode for an unknown value', function () {
        $html = Blade::render("<x-synapse-datepicker mode=\"'); alert(1); ('\" />");

        expect($html)->toContain('synapseDatepicker(null, {})')
            ->not->toContain('alert(1)');
    });

    it('keeps a caller flatpickr config alongside the mode', function () {
        $html = Blade::render('<x-synapse-datepicker mode="month" :flatpickr="json_encode([\'dateFormat\' => \'m/Y\'])" />');

        expect($html)->toContain('synapseDatepicker(null, {&quot;dateFormat&quot;:&quot;m\/Y&quot;}, &#039;month&#039;)');
    });

    // The package has no JS test harness, so this is the only automated guard that the
    // month grid is actually wired up in the stub the host apps run.
    it('wires the flatpickr monthSelect plugin and its stylesheet in the components stub', function () {
        $js = (string) file_get_contents(dirname(__DIR__, 3).'/stubs/synapps/resources/js/synapse-components.js');

        expect($js)->toContain('import monthSelectPlugin from "flatpickr/dist/plugins/monthSelect";')
            ->and($js)->toContain('import "flatpickr/dist/plugins/monthSelect/style.css";')
            ->and($js)->toContain('monthSelectPlugin(');
    });
});
