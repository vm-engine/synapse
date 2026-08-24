@props([
    'id' => '',
    'name' => '',
    'label' => '',
    'checked' => false,
    'activeColor' => 'green',
    'inline' => false,
])

@php
    $colorClasses = [
        'green' => 'bg-green-500 dark:bg-green-500',
        'blue' => 'bg-blue-500 dark:bg-blue-500',
        'brand' => 'bg-brand-500 dark:bg-brand-500',
        'red' => 'bg-red-500 dark:bg-red-500',
        'yellow' => 'bg-yellow-500 dark:bg-yellow-500',
        'purple' => 'bg-purple-500 dark:bg-purple-500',
    ];

    $activeColorClass = $colorClasses[$activeColor] ?? $colorClasses['green'];
    $componentId = $id ?: ($name ?: 'toggler-' . uniqid());
    $checkedValue = $checked ? 'true' : 'false';
@endphp

@if ($inline)
    <div
        data-checked="{{ $checkedValue }}"
        x-data="synapseTogglerInline({{ $checkedValue }})"
    >
        <label
            class="flex cursor-pointer select-none items-center gap-2 text-sm font-medium"
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
                    class="block h-6 w-11 rounded-full transition-colors"
                    :class="active ? '{{ $activeColorClass }}' : 'bg-gray-300 dark:bg-gray-600'"
                ></div>
                <div
                    class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow-sm transition-transform duration-300 ease-in-out"
                    :class="active ? 'translate-x-full' : 'translate-x-0'"
                ></div>
            </div>
        </label>
    </div>
@else
    <div x-data="synapseToggler($wire.entangle('{{ $attributes->wire('model')->value() }}').live)">
        <label
            class="flex cursor-pointer select-none items-center gap-3 text-sm font-medium text-gray-700 dark:text-gray-400"
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
                    class="block h-6 w-11 rounded-full transition-colors"
                    :class="active ? '{{ $activeColorClass }}' : 'bg-gray-300 dark:bg-gray-600'"
                ></div>
                <div
                    class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow-sm transition-transform duration-300 ease-in-out"
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

<script nonce="{{ csp_nonce() }}">
    (() => {
        const register = () => {
        Alpine.data('synapseTogglerInline', (checkedValue) => ({
            active: checkedValue,
            init() {
                this.active = this.$el.dataset.checked === 'true';
                new MutationObserver(() => {
                    this.active = this.$el.dataset.checked === 'true';
                }).observe(this.$el, {
                    attributes: true,
                    attributeFilter: ['data-checked']
                });
            }
        }));

        Alpine.data('synapseToggler', (initialActive) => ({
            active: initialActive,
        }));
        };
    document.addEventListener('alpine:init', register);
    if (window.Alpine) {
        register();
    }
    })();
</script>
