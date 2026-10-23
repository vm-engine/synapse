{{--
    Field Component

    A form-box row: label, wire-bound input and its error/hint, linked for screen readers
    (label for, aria-invalid, aria-describedby).

    Props:
    - name (string): wire:model path, also the validation error key unless `error` is given
    - error (string): Validation error key when it differs from the model path (e.g. name="profile.phone" error="phone")
    - prefix (string): Text shown before the input in an input group, e.g. 'Rp'
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
    $describedBy = trim(($errors->has($error) ? $id.'-error ' : '').($hint ? $id.'-hint' : ''));
    $wireModel = 'wire:model'.($modifier !== '' ? '.'.$modifier : '');
@endphp
<div @class(['form-box', 'required' => $required]) {{ $attributes->only('class') }}>
    <label for="{{ $id }}">{{ $label }}</label>
    @if ($slot->isEmpty())
        @if ($prefix)
            <div class="input-group">
                <span class="input-group-item left">{{ $prefix }}</span>
        @endif
        <input
            @class(['form-input', 'has-error' => $errors->has($error), 'pl-[62px]!' => $prefix])
            id="{{ $id }}"
            type="{{ $type }}"
            {{ $wireModel }}="{{ $name }}"
            @if ($step) step="{{ $step }}" @endif
            @if ($inputmode) inputmode="{{ $inputmode }}" @endif
            @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
            @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
            @if ($errors->has($error)) aria-invalid="true" @endif
            @if ($required) aria-required="true" @endif
            @disabled($disabled)
            {{ $attributes->except('class') }}
        >
        @if ($prefix)
            </div>
        @endif
    @else
        {{ $slot }}
    @endif
    @if ($hint)
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" id="{{ $id }}-hint">{{ $hint }}</p>
    @endif
    @if ($errors->has($error))
        <p class="form-error-message" id="{{ $id }}-error">{{ $errors->first($error) }}</p>
    @endif
</div>
