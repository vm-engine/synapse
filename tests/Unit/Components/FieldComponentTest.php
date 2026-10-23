<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use VmEngine\Synapse\Components\Field;

describe('Field Component', function () {
    beforeEach(function () {
        view()->addNamespace('synapse', __DIR__.'/../../../resources/views');
        Blade::component(Field::class, 'synapse-field');
        view()->share('errors', new ViewErrorBag);
    });

    it('derives the id from the wire model name', function () {
        expect((new Field(name: 'form.bank_name', label: 'Bank'))->id)->toBe('f-form-bank_name');
    });

    it('renders a labelled wire-bound input', function () {
        $html = Blade::render('<x-synapse-field name="amount" label="Nominal" type="number" inputmode="decimal" required />');

        expect($html)->toContain('<label for="f-amount">Nominal</label>')
            ->toContain('id="f-amount"')
            ->toContain('wire:model="amount"')
            ->toContain('inputmode="decimal"')
            ->toContain('aria-required="true"')
            ->not->toContain(' required>')
            ->toContain('form-box required')
            ->not->toContain('aria-invalid');
    });

    it('wires the error message to the input', function () {
        $errors = (new ViewErrorBag)->put('default', new MessageBag(['amount' => ['Too small']]));
        view()->share('errors', $errors);

        $html = Blade::render('<x-synapse-field name="amount" label="Nominal" />');

        expect($html)->toContain('aria-invalid="true"')
            ->toContain('aria-describedby="f-amount-error"')
            ->toContain('id="f-amount-error"')
            ->toContain('Too small')
            ->toContain('has-error');
    });

    it('renders the slot instead of the default input', function () {
        $html = Blade::render('<x-synapse-field name="group" label="Kelompok"><select id="f-group"></select></x-synapse-field>');

        expect($html)->toContain('<select id="f-group"></select>')->not->toContain('<input');
    });

    it('renders a hint tied to the input', function () {
        $html = Blade::render('<x-synapse-field name="amount" label="Nominal" hint="Minimal Rp10.000" />');

        expect($html)->toContain('id="f-amount-hint"')->toContain('aria-describedby="f-amount-hint"');
    });

    it('applies a wire:model modifier', function () {
        expect(Blade::render('<x-synapse-field name="q" label="Q" modifier="live" />'))->toContain('wire:model.live="q"');
    });

    it('disables the input', function () {
        expect(Blade::render('<x-synapse-field name="a" label="A" :disabled="true" />'))->toContain('disabled');
    });
});
