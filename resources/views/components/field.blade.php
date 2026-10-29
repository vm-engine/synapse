{{--
    Field Component

    A form-box row: label, wire-bound input and its error/hint, linked for screen readers
    (label for, aria-invalid, aria-describedby).

    Props:
    - name (string): wire:model path, also the validation error key unless `error` is given
    - error (string): Validation error key when it differs from the model path (e.g. name="profile.phone" error="phone")
    - prefix (string): Text shown before the input in an input group, e.g. 'Rp'
    - type="currency": renders <x-synapse-currency raw> (thousands separators while typing, the plain
      number on wire:model); `prefix` is its symbol and `decimals` (default 0) the decimal places
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
    $errors ??= new ViewErrorBag();
    $describedBy = trim(($errors->has($error) ? $id . '-error ' : '') . ($hint ? $id . '-hint' : ''));
    $wireModel = 'wire:model' . ($modifier !== '' ? '.' . $modifier : '');
@endphp
{{-- only('class') before merge(): a bare merge() would also print pass-through
     attributes (maxlength, readonly, data-*) on the wrapper, where they mean nothing. --}}
<div {{ $attributes->only('class')->merge(['class' => 'form-box' . ($required ? ' required' : '')]) }}>
    <label
        for="{{ $id }}"
        id="{{ $id }}-label"
    >{{ $label }}</label>
    @if ($slot->isEmpty() && $type === 'currency')
        <x-synapse-currency
            raw
            :currency="$prefix ?? ''"
            :decimals="$decimals"
            :id="$id"
            :class="trim($inputClass . ($errors->has($error) ? ' has-error' : ''))"
            :aria-describedby="$describedBy !== '' ? $describedBy : null"
            :aria-invalid="$errors->has($error) ? 'true' : null"
            :aria-required="$required ? 'true' : null"
            :disabled="$disabled"
            {{ $attributes->except('class')->merge([$wireModel => $name]) }}
        />
    @elseif ($slot->isEmpty() && $type === 'textarea')
        <textarea
            @class([
                'form-input',
                $inputClass => $inputClass !== '',
                'has-error' => $errors->has($error),
            ])
            id="{{ $id }}"
            rows="{{ $rows }}"
            {{ $wireModel }}="{{ $name }}"
            @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
            @if ($errors->has($error)) aria-invalid="true" @endif
            @if ($required) aria-required="true" @endif
            @disabled($disabled)
            {{ $attributes->except('class') }}
        ></textarea>
    @elseif ($slot->isEmpty())
        @if ($prefix)
            <div class="input-group">
                <span class="input-group-item left">{{ $prefix }}</span>
        @endif
        <input
            @class([
                'form-input',
                $inputClass => $inputClass !== '',
                'has-error' => $errors->has($error),
                'pl-[62px]!' => $prefix,
            ])
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
    <p
        class="form-help"
        id="{{ $id }}-hint"
    >{{ $hint }}</p>
@endif
@if ($errors->has($error))
    <p
        class="form-error-message"
        id="{{ $id }}-error"
    >{{ $errors->first($error) }}</p>
@endif
</div>
