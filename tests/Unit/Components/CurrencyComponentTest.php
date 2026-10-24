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

        expect($html)->toContain("synapseCurrency(\$wire.entangle('deposit.amount'), 2)")
            ->toContain('x-model="display"')
            ->toContain('id="f-amount"')
            ->toContain('inputmode="decimal"')
            ->not->toContain('wire:model=');
    });

    it('entangles live when the model is live', function () {
        expect(Blade::render('<x-synapse-currency raw wire:model.live="amount" />'))->toContain("\$wire.entangle('amount').live");
    });

    it('renders a raw currency input from a currency field, wired for screen readers', function () {
        $html = Blade::render('<x-synapse-field name="amount" label="Nominal" type="currency" prefix="Rp" :decimals="2" hint="Minimal" required />');

        expect($html)->toContain('<label for="f-amount">Nominal</label>')
            ->toContain("synapseCurrency(\$wire.entangle('amount'), 2)")
            ->toContain('id="f-amount"')
            ->toContain('aria-describedby="f-amount-hint"')
            ->toContain('aria-required="true"')
            ->toContain('>Rp</span>');
    });
});
