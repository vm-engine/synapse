<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use VmEngine\Synapse\Components\Currency;
use VmEngine\Synapse\Components\Field;

describe('Field Component', function () {
    beforeEach(function () {
        view()->addNamespace('synapse', __DIR__.'/../../../resources/views');
        Blade::component(Field::class, 'synapse-field');
        // field.blade.php references <x-synapse-currency>, which Blade resolves at compile time
        // whatever the runtime branch, so it must be registered here too.
        Blade::component(Currency::class, 'synapse-currency');
        view()->share('errors', new ViewErrorBag);
    });

    it('derives the id from the wire model name', function () {
        expect((new Field(name: 'form.bank_name', label: 'Bank'))->id)->toBe('f-form-bank_name');
    });

    it('renders a labelled wire-bound input', function () {
        $html = Blade::render('<x-synapse-field name="amount" label="Nominal" type="number" inputmode="decimal" required />');

        expect($html)->toMatch('/<label[^>]*for="f-amount"[^>]*id="f-amount-label"[^>]*>Nominal<\/label>/s')
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

    it('reads errors from a separate key when the model path differs', function () {
        view()->share('errors', (new ViewErrorBag)->put('default', new MessageBag(['phone' => ['Required']])));

        $html = Blade::render('<x-synapse-field name="profile.phone" error="phone" label="Phone" />');

        expect($html)->toContain('wire:model="profile.phone"')->toContain('id="f-profile-phone"')
            ->toContain('Required')->toContain('aria-invalid="true"');
    });

    it('renders a prefix in an input group', function () {
        $html = Blade::render('<x-synapse-field name="amount" label="Nominal" prefix="Rp" />');

        expect($html)->toContain('input-group')->toContain('>Rp</span>')->toContain('wire:model="amount"');
    });

    it('disables the input', function () {
        expect(Blade::render('<x-synapse-field name="a" label="A" :disabled="true" />'))->toContain('disabled');
    });

    it('merges a caller class into the form-box and puts inputClass on the input', function () {
        $html = Blade::render('<x-synapse-field name="qty" label="Qty" class="sm:col-span-2" input-class="text-right" required />');

        expect($html)->toMatch('/<div class="form-box required sm:col-span-2"\s*>/')
            ->toMatch('/<input\s+class="form-input text-right/')
            ->not->toContain('input-class=');
    });

    it('keeps pass-through attributes off the wrapper', function () {
        $html = Blade::render('<x-synapse-field name="code" label="Code" maxlength="20" />');

        expect($html)->toMatch('/<div class="form-box[^"]*"\s*>/')
            ->toMatch('/<input[^>]*maxlength="20"/');
    });

    it('gives the label an id so a slot control can be named by it', function () {
        $html = Blade::render('<x-synapse-field name="clientId" label="Client"><div role="combobox"></div></x-synapse-field>');

        expect($html)->toMatch('/<label[^>]*for="f-clientId"[^>]*id="f-clientId-label"/s');
    });

    it('renders a textarea with rows when asked', function () {
        $html = Blade::render('<x-synapse-field name="notes" label="Notes" type="textarea" rows="4" hint="Optional" />');

        expect($html)->toMatch('/<textarea[^>]*id="f-notes"/')
            ->toMatch('/<textarea[^>]*rows="4"/')
            ->toMatch('/<textarea[^>]*wire:model="notes"/')
            ->toContain('aria-describedby="f-notes-hint"')
            ->not->toContain('<input');
    });

    it('styles the hint with the form-help class', function () {
        $html = Blade::render('<x-synapse-field name="code" label="Code" hint="Leave blank to auto-generate" />');

        expect($html)->toMatch('/<p[^>]*class="form-help"[^>]*id="f-code-hint"/s');
    });
});
