{{--
    Form Input Component

    Usage:
    <x-theme::form-input
        label="Email Address"
        type="email"
        name="email"
        :error="$errors->first('email')"
        hint="We'll never share your email"
        required
    />
--}}

@props([
    'label' => null,
    'type' => 'text',
    'error' => null,
    'hint' => null,
    'required' => false,
])

@php
    $inputId = $attributes->get('id', $attributes->get('name', 'input_' . uniqid()));
    $hasError = !is_null($error) && !empty($error);

    $inputClasses =
        'w-full px-4 py-2.5 rounded-lg border bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:border-transparent transition-all disabled:bg-gray-100 dark:disabled:bg-gray-900 disabled:cursor-not-allowed';

    if ($hasError) {
        $inputClasses .= ' border-red-500 focus:ring-red-500';
    } else {
        $inputClasses .= ' border-gray-300 dark:border-gray-700 focus:ring-primary-500';
    }
@endphp

<div class="mb-4">
    {{-- Label --}}
    @if ($label)
        <label
            class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
            for="{{ $inputId }}"
        >
            {{ $label }}
            @if ($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    {{-- Input --}}
    <input
        id="{{ $inputId }}"
        type="{{ $type }}"
        {{ $attributes->merge(['class' => $inputClasses]) }}
    >

    {{-- Hint --}}
    @if ($hint && !$hasError)
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $hint }}</p>
    @endif

    {{-- Error --}}
    @if ($hasError)
        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $error }}</p>
    @endif
</div>
