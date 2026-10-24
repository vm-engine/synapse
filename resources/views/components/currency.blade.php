{{--
    Currency Component

    Masked money input: ',' decimal separator, '.' thousands separator.

    Props:
    - currency (string): Symbol shown before the input (default: 'Rp.')
    - decimals (int): Decimal places allowed (default: 0)
    - raw (bool): wire:model receives the plain number ("1250000.50") instead of the formatted text
      ("1.250.000,50"). Default false keeps the formatted text for existing callers.

    Other attributes (id, aria-*, class, …) go to the visible input.
--}}
@php($wireModel = $attributes->wire('model'))
<div class="input-group">
    @if ($currency !== '')
        <span class="input-group-item left">{{ $currency }}</span>
    @endif
    @if ($raw && $wireModel->value())
        <input
            type="text"
            inputmode="decimal"
            x-data="synapseCurrency($wire.entangle('{{ $wireModel->value() }}'){{ $wireModel->hasModifier('live') ? '.live' : '' }}, {{ $decimals }})"
            x-model="display"
            x-mask:dynamic="$money($input, ',', '.', decimals)"
            x-on:focus="$event.target.value == '0' && $event.target.select()"
            {{ $attributes->whereDoesntStartWith('wire:model')->class(['form-input', 'pl-[62px]!' => $currency !== '']) }}
        >
    @else
        <input
            type="text"
            inputmode="{{ $decimals > 0 ? 'decimal' : 'numeric' }}"
            x-mask:dynamic="$money($input, ',', '.', {{ $decimals }})"
            x-on:focus="$event.target.value == '0' && $event.target.select()"
            {{ $attributes->class(['form-input', 'pl-[62px]!' => $currency !== '']) }}
        >
    @endif
</div>
