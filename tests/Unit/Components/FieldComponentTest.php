<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use VmEngine\Synapse\Components\AdvSelect;
use VmEngine\Synapse\Components\Currency;
use VmEngine\Synapse\Components\Datepicker;
use VmEngine\Synapse\Components\Field;

describe('Field Component', function () {
    beforeEach(function () {
        view()->addNamespace('synapse', __DIR__.'/../../../resources/views');
        Blade::component(Field::class, 'synapse-field');
        // field.blade.php references <x-synapse-currency>, which Blade resolves at compile time
        // whatever the runtime branch, so it must be registered here too.
        Blade::component(Currency::class, 'synapse-currency');
        Blade::component(AdvSelect::class, 'synapse-adv-select');
        Blade::component(Datepicker::class, 'synapse-datepicker');
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

    it('exposes the aria wiring for slotted controls to consume', function () {
        $errors = (new ViewErrorBag)->put('default', new MessageBag(['amount' => ['Too small']]));
        view()->share('errors', $errors);

        $field = new Field(name: 'amount', label: 'Nominal', hint: 'Minimal', required: true);

        expect($field->fieldAria)->toBe([
            'aria-labelledby' => 'f-amount-label',
            'aria-describedby' => 'f-amount-error f-amount-hint',
            'aria-invalid' => 'true',
            'aria-required' => 'true',
        ]);
    });

    it('leaves out the aria it has nothing to point at', function () {
        expect((new Field(name: 'amount', label: 'Nominal'))->fieldAria)
            ->toBe(['aria-labelledby' => 'f-amount-label']);
    });

    it('wires a slotted adv-select to the label, error and hint with no call-site aria', function () {
        $errors = (new ViewErrorBag)->put('default', new MessageBag(['clientId' => ['Pick one']]));
        view()->share('errors', $errors);

        $html = Blade::render(
            '<x-synapse-field name="clientId" label="Client" hint="Billing" required>'
            .'<x-synapse-adv-select wire-model="clientId" />'
            .'</x-synapse-field>'
        );

        expect($html)->toContain('aria-labelledby="f-clientId-label"')
            ->toContain('aria-describedby="f-clientId-error f-clientId-hint"')
            ->toContain('aria-invalid="true"')
            ->toContain('aria-required="true"');
    });

    it('lets a slotted control override the aria the field supplies', function () {
        $html = Blade::render(
            '<x-synapse-field name="clientId" label="Client">'
            .'<x-synapse-adv-select wire-model="clientId" aria-label="Pick a client" />'
            .'</x-synapse-field>'
        );

        expect($html)->toContain('aria-label="Pick a client"')
            ->not->toContain('aria-labelledby');
    });

    it('wires a slotted datepicker to the error with no call-site aria', function () {
        $errors = (new ViewErrorBag)->put('default', new MessageBag(['dueAt' => ['Required']]));
        view()->share('errors', $errors);

        $html = Blade::render(
            '<x-synapse-field name="dueAt" label="Due">'
            .'<x-synapse-datepicker wire:model="dueAt" />'
            .'</x-synapse-field>'
        );

        expect($html)->toMatch('/<input[^>]*aria-describedby="f-dueAt-error"/')
            ->toMatch('/<input[^>]*aria-invalid="true"/')
            ->toMatch('/<input[^>]*class="form-input"/');
    });

    it('styles the hint with the form-help class', function () {
        $html = Blade::render('<x-synapse-field name="code" label="Code" hint="Leave blank to auto-generate" />');

        expect($html)->toMatch('/<p[^>]*class="form-help"[^>]*id="f-code-hint"/s');
    });
});
