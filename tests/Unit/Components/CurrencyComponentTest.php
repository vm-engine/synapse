<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ViewErrorBag;
use VmEngine\Synapse\Components\Currency;
use VmEngine\Synapse\Components\Field;

describe('Currency Component', function () {
    beforeEach(function () {
        view()->addNamespace('synapse', __DIR__.'/../../../resources/views');
        Blade::component(Currency::class, 'synapse-currency');
        Blade::component(Field::class, 'synapse-field');
        view()->share('errors', new ViewErrorBag);
    });

    it('keeps whole-number masking and the formatted wire value by default', function () {
        $html = Blade::render('<x-synapse-currency currency="Rp" wire:model="amount" />');

        expect($html)->toContain("\$money(\$input, ',', '.', 0)")
            ->toContain('wire:model="amount"')
            ->not->toContain('synapseCurrency');
    });

    it('masks the given number of decimals', function () {
        expect(Blade::render('<x-synapse-currency :decimals="2" wire:model="amount" />'))->toContain("\$money(\$input, ',', '.', 2)");
    });

    it('sends the plain number to Livewire in raw mode', function () {
        $html = Blade::render('<x-synapse-currency raw :decimals="2" id="f-amount" wire:model="deposit.amount" />');

        expect($html)->toContain("synapseCurrency(\$wire.entangle('deposit.amount'), 2, null)")
            ->toContain('x-model="display"')
            ->toContain('id="f-amount"')
            ->toContain('inputmode="decimal"')
            ->not->toContain('wire:model=');
    });

    it('stays deferred and passes no sync delay without .live', function () {
        expect(Blade::render('<x-synapse-currency raw wire:model="amount" />'))->toContain("synapseCurrency(\$wire.entangle('amount'), 0, null)");
    });

    it('syncs a live model after a debounce instead of on every keystroke', function () {
        // Entangling with .live sends a request per keystroke and bypasses wire:model's debounce.
        expect(Blade::render('<x-synapse-currency raw wire:model.live="amount" />'))
            ->toContain("synapseCurrency(\$wire.entangle('amount'), 0, 150)")
            ->not->toContain('.live,');
    });

    it('honours an explicit debounce', function () {
        expect(Blade::render('<x-synapse-currency raw wire:model.live.debounce.500ms="amount" />'))->toContain("synapseCurrency(\$wire.entangle('amount'), 0, 500)")
            ->and(Blade::render('<x-synapse-currency raw wire:model.live.debounce="amount" />'))->toContain("synapseCurrency(\$wire.entangle('amount'), 0, 250)");
    });

    it('selects a zero value on focus so typing replaces it, whatever the decimals', function () {
        // '0' with no decimals, '0,00' with two: typing into either must not append to the zero.
        foreach ([
            '<x-synapse-currency wire:model="amount" />',
            '<x-synapse-currency :decimals="2" wire:model="amount" />',
            '<x-synapse-currency raw :decimals="2" wire:model="amount" />',
        ] as $template) {
            expect(Blade::render($template))
                ->toContain(
                    // Not a regex: Alpine's CSP-safe build cannot parse a regex literal.
                    "x-on:focus=\"\$event.target.value.replaceAll('0', '').replaceAll('.', '').replaceAll(',', '') == '' && \$event.target.select()\""
                );
        }
    });

    it('renders a raw currency input from a currency field, wired for screen readers', function () {
        $html = Blade::render('<x-synapse-field name="amount" label="Nominal" type="currency" prefix="Rp" :decimals="2" hint="Minimal" required />');

        expect($html)->toMatch('/<label[^>]*for="f-amount"[^>]*>Nominal<\/label>/s')
            ->toContain("synapseCurrency(\$wire.entangle('amount'), 2, null)")
            ->toContain('id="f-amount"')
            ->toContain('aria-describedby="f-amount-hint"')
            ->toContain('aria-required="true"')
            ->toContain('>Rp</span>');
    });
});
