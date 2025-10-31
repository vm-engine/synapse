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
@endphp

@if($inline)
<div x-data="{ active: {{ $checked ? 'true' : 'false' }} }">
    <label
        for="{{ $componentId }}"
        class="flex cursor-pointer items-center gap-2 text-sm font-medium select-none"
        {{ $attributes->only(['title']) }}
    >
        <div class="relative">
            <input
                type="checkbox"
                id="{{ $componentId }}"
                name="{{ $name }}"
                class="sr-only"
                :checked="active"
                {{ $attributes->only(['@change']) }}
            />
            <div
                class="block h-6 w-11 rounded-full transition-colors"
                :class="active ? '{{ $activeColorClass }}' : 'bg-gray-300 dark:bg-gray-600'"
            ></div>
            <div
                class="absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white shadow-sm transition-transform duration-300 ease-in-out"
                :class="active ? 'translate-x-full' : 'translate-x-0'"
            ></div>
        </div>
    </label>
</div>
@else
<div x-data="{ active: @entangle($attributes->wire('model')->value()) }">
    <label
        for="{{ $componentId }}"
        class="flex cursor-pointer items-center gap-3 text-sm font-medium text-gray-700 dark:text-gray-400 select-none"
    >
        <div class="relative">
            <input
                type="checkbox"
                id="{{ $componentId }}"
                name="{{ $name }}"
                class="sr-only"
                x-model="active"
            />
            <div
                class="block h-6 w-11 rounded-full transition-colors"
                :class="active ? '{{ $activeColorClass }}' : 'bg-gray-300 dark:bg-gray-600'"
            ></div>
            <div
                class="absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white shadow-sm transition-transform duration-300 ease-in-out"
                :class="active ? 'translate-x-full' : 'translate-x-0'"
            ></div>
        </div>
        @if($label)
            <span>{{ $label }}</span>
        @else
            <span x-text="active ? 'Active' : 'Inactive'"></span>
        @endif
    </label>
</div>
@endif

