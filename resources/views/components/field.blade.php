{{--
    Field Component

    A form-box row: label, wire-bound input and its error/hint, linked for screen readers
    (label for, aria-invalid, aria-describedby).

    Props:
    - name (string): wire:model path, also the validation error key
    - label (string): Visible label
    - type (string): Input type (default: 'text')
    - required (bool) / disabled (bool): required marks the box and sets aria-required (no native
      `required`, so the server-side message shows instead of the browser's validation tooltip)
    - hint (string): Helper text under the input
    - step, inputmode, autocomplete (string): Passed to the input
    - modifier (string): wire:model modifier, e.g. 'live' or 'blur'
    - id (string): Input id (default: 'f-' + name with dots as dashes)

    Slot: replaces the default <input> (select, x-synapse-currency, datepicker…); give that control id="{{ $id }}".

    Usage:
    <x-synapse-field name="amount" label="Nominal" type="number" inputmode="decimal" hint="Minimal Rp10.000" required />
--}}
@use('Illuminate\Support\ViewErrorBag')
@php
    $errors ??= new ViewErrorBag;
    $describedBy = trim(($errors->has($name) ? $id.'-error ' : '').($hint ? $id.'-hint' : ''));
    $wireModel = 'wire:model'.($modifier !== '' ? '.'.$modifier : '');
@endphp
<div @class(['form-box', 'required' => $required]) {{ $attributes->only('class') }}>
    <label for="{{ $id }}">{{ $label }}</label>
    @if ($slot->isEmpty())
        <input
            class="form-input @error($name) has-error @enderror"
            id="{{ $id }}"
            type="{{ $type }}"
            {{ $wireModel }}="{{ $name }}"
            @if ($step) step="{{ $step }}" @endif
            @if ($inputmode) inputmode="{{ $inputmode }}" @endif
            @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
            @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
            @error($name) aria-invalid="true" @enderror
            @if ($required) aria-required="true" @endif
            @disabled($disabled)
            {{ $attributes->except('class') }}
        >
    @else
        {{ $slot }}
    @endif
    @if ($hint)
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" id="{{ $id }}-hint">{{ $hint }}</p>
    @endif
    @error($name)
        <p class="form-error-message" id="{{ $id }}-error">{{ $message }}</p>
    @enderror
</div>
