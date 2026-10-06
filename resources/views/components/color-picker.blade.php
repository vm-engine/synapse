{{--
    Color Picker Component

    Native <input type="color"> + hex text field + optional preset swatches,
    bound to a Livewire property via wire:model (entangled, deferred).
    Value format: #rrggbb — validate with Laravel's `hex_color` rule.

    Props:
    - swatches (array): Preset #rrggbb colors (invalid entries are dropped)
    - allowCustom (bool): Show the native picker + hex field (default: true; forced on when no swatches)

    Usage:
    <x-synapse-color-picker wire:model="form.color" :swatches="['#ef4444', '#3b82f6', '#10b981']" />
--}}

<div
    class="syn-color-picker"
    wire:ignore
    @if ($attributes->wire('model')->value()) x-data="synapseColorPicker($wire.entangle('{{ $attributes->wire('model')->value() }}'){{ $attributes->wire('model')->hasModifier('live') ? '.live' : '' }})"
    @else
        x-data="synapseColorPicker(null)" @endif
    {{ $attributes->whereDoesntStartWith('wire:model') }}
>
    @if ($allowCustom)
        <input
            class="syn-color-picker-native"
            type="color"
            aria-label="{{ __('Pick a color') }}"
            :value="nativeValue()"
            @input="fromNative"
        >
        <input
            class="form-input syn-color-picker-hex"
            type="text"
            maxlength="7"
            placeholder="#000000"
            spellcheck="false"
            aria-label="{{ __('Hex color') }}"
            x-model="value"
        >
    @endif

    @if ($swatches)
        <div class="syn-color-picker-swatches">
            @foreach ($swatches as $swatch)
                <button
                    class="syn-color-swatch"
                    type="button"
                    style="background-color: {{ $swatch }}"
                    title="{{ $swatch }}"
                    aria-label="{{ $swatch }}"
                    :class="{ 'syn-color-swatch-selected': isSelected('{{ $swatch }}') }"
                    @click="pick('{{ $swatch }}')"
                ></button>
            @endforeach
        </div>
    @endif
</div>
