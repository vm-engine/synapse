{{--
    Currency Component

    Masked money input: ',' decimal separator, '.' thousands separator.

    Props:
    - currency (string): Symbol shown before the input (default: 'Rp.')
    - decimals (int): Decimal places allowed (default: 0)
    - raw (bool): wire:model receives the plain number ("1250000.50") instead of the formatted text
      ("1.250.000,50"). Default false keeps the formatted text for existing callers. In raw mode
      wire:model.live syncs like wire:model does: one request after typing pauses (150ms, or
      .debounce.Nms), not one per keystroke.

    Other attributes (id, aria-*, class, …) go to the visible input.
--}}
@php
    $wireModel = $attributes->wire('model');
    // Live sync delay for raw mode, matching wire:model: 150ms for .live, 250ms for a bare .debounce, else .debounce.Nms.
    $syncDelay = null;
    if ($wireModel->hasModifier('live')) {
        $syncDelay = $wireModel->hasModifier('debounce') ? 250 : 150;
        foreach ($wireModel->modifiers() as $modifier) {
            if (preg_match('/^(\d+)ms$/', (string) $modifier, $match) === 1) {
                $syncDelay = (int) $match[1];
            }
        }
    }
@endphp
{{-- A value that is all zeros and separators ("0", "0,00") is selected on focus, so typing replaces it
     instead of appending to it; any real amount keeps the caret where the user clicked. Written as
     replaceAll() chains, not a regex: Alpine's CSP-safe build cannot parse a regex literal. --}}
<div class="input-group">
    @if ($currency !== '')
        <span class="input-group-item left">{{ $currency }}</span>
    @endif
    @if ($raw && $wireModel->value())
        <input
            type="text"
            inputmode="decimal"
            x-data="synapseCurrency($wire.entangle('{{ $wireModel->value() }}'), {{ $decimals }}, {{ $syncDelay ?? 'null' }})"
            x-model="display"
            x-mask:dynamic="$money($input, ',', '.', decimals)"
            x-on:focus="$event.target.value.replaceAll('0', '').replaceAll('.', '').replaceAll(',', '') == '' && $event.target.select()"
            {{ $attributes->whereDoesntStartWith('wire:model')->class(['form-input', 'pl-[62px]!' => $currency !== '']) }}
        >
    @else
        <input
            type="text"
            inputmode="{{ $decimals > 0 ? 'decimal' : 'numeric' }}"
            x-mask:dynamic="$money($input, ',', '.', {{ $decimals }})"
            x-on:focus="$event.target.value.replaceAll('0', '').replaceAll('.', '').replaceAll(',', '') == '' && $event.target.select()"
            {{ $attributes->class(['form-input', 'pl-[62px]!' => $currency !== '']) }}
        >
    @endif
</div>
