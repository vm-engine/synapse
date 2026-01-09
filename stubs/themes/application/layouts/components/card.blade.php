{{--
    Card Component

    Usage:
    <x-theme::card variant="default|glass|bordered" padding="sm|md|lg">
        Content here
    </x-theme::card>
--}}

@props([
    'variant' => 'default', // default, glass, bordered
    'padding' => 'md', // sm, md, lg
])

@php
    $baseClasses = 'rounded-xl transition-all duration-200';

    $variantClasses = [
        'default' => 'bg-white dark:bg-gray-800 shadow-md border border-gray-200 dark:border-gray-700',
        'glass' =>
            'bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl shadow-xl border border-gray-200/50 dark:border-gray-700/50',
        'bordered' => 'bg-transparent border-2 border-gray-300 dark:border-gray-600',
    ];

    $paddingClasses = [
        'sm' => 'p-4',
        'md' => 'p-6',
        'lg' => 'p-8',
    ];

    $classes = $baseClasses . ' ' . $variantClasses[$variant] . ' ' . $paddingClasses[$padding];
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</div>
