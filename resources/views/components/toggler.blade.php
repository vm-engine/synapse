@props([
    'id' => '',
    'name' => '',
    'label' => '',
    'checked' => false,
    'activeColor' => 'green',
    'inline' => false,
])

@php
    $allowedColors = ['green', 'blue', 'brand', 'red', 'yellow', 'purple'];
    $activeColorClass = 'syn-toggler-' . (in_array($activeColor, $allowedColors) ? $activeColor : 'green');
    $componentId = $id ?: ($name ?: 'toggler-' . uniqid());
    $checkedValue = $checked ? 'true' : 'false';
@endphp

@if ($inline)
    <div
        data-checked="{{ $checkedValue }}"
        x-data="synapseTogglerInline({{ $checkedValue }})"
    >
        <label
            class="syn-toggler-label-inline"
            for="{{ $componentId }}"
            {{ $attributes->only(['title', 'wire:click']) }}
        >
            <div class="relative">
                <input
                    class="sr-only"
                    id="{{ $componentId }}"
                    name="{{ $name }}"
                    type="checkbox"
                    x-model="active"
                    {{ $attributes->only(['@change', 'wire:model', 'wire:loading']) }}
                />
                <div
                    class="syn-toggler-track"
                    :class="active ? '{{ $activeColorClass }}' : 'syn-toggler-off'"
                ></div>
                <div
                    class="syn-toggler-thumb"
                    :class="active ? 'translate-x-full' : 'translate-x-0'"
                ></div>
            </div>
        </label>
    </div>
@else
    <div x-data="synapseToggler($wire.entangle('{{ $attributes->wire('model')->value() }}').live)">
        <label
            class="syn-toggler-label"
            for="{{ $componentId }}"
        >
            <div class="relative">
                <input
                    class="sr-only"
                    id="{{ $componentId }}"
                    name="{{ $name }}"
                    type="checkbox"
                    x-model="active"
                />
                <div
                    class="syn-toggler-track"
                    :class="active ? '{{ $activeColorClass }}' : 'syn-toggler-off'"
                ></div>
                <div
                    class="syn-toggler-thumb"
                    :class="active ? 'translate-x-full' : 'translate-x-0'"
                ></div>
            </div>
            @if ($label)
                <span>{{ $label }}</span>
            @else
                <span x-text="active ? 'Active' : 'Inactive'"></span>
            @endif
        </label>
    </div>
@endif

