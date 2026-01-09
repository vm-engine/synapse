{{--
    Button Component

    Usage:
    <x-theme::button variant="primary|secondary|danger|ghost" size="sm|md|lg">
        Click me
    </x-theme::button>
--}}

@props([
    'variant' => 'primary', // primary, secondary, danger, ghost
    'size' => 'md', // sm, md, lg
    'type' => 'button',
])

@php
    $baseClasses =
        'inline-flex items-center justify-center font-medium rounded-lg transition-all duration-200 cursor-pointer focus:outline-none focus:ring-2 focus:ring-offset-2 dark:ring-offset-gray-900 disabled:opacity-50 disabled:cursor-not-allowed';

    $variantClasses = [
        'primary' =>
            'bg-primary-600 hover:bg-primary-700 text-white shadow-lg shadow-primary-600/30 focus:ring-primary-500',
        'secondary' =>
            'bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-900 dark:text-gray-100 focus:ring-gray-500',
        'danger' => 'bg-red-600 hover:bg-red-700 text-white shadow-lg shadow-red-600/30 focus:ring-red-500',
        'ghost' =>
            'bg-transparent hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 focus:ring-gray-500',
    ];

    $sizeClasses = [
        'sm' => 'px-3 py-1.5 text-sm',
        'md' => 'px-4 py-2 text-base',
        'lg' => 'px-6 py-3 text-lg',
    ];

    $classes = $baseClasses . ' ' . $variantClasses[$variant] . ' ' . $sizeClasses[$size];
@endphp

<button
    type="{{ $type }}"
    {{ $attributes->merge(['class' => $classes]) }}
>
    {{ $slot }}
</button>
