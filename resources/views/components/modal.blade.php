@props([
    'name' => 'modal',
    'maxWidth' => 'xl',
    'title' => null,
])

@php
    $maxWidthClass =
        [
            'sm' => 'max-w-sm',
            'md' => 'max-w-md',
            'lg' => 'max-w-lg',
            'xl' => 'max-w-xl',
            '2xl' => 'max-w-2xl',
            '3xl' => 'max-w-3xl',
            '4xl' => 'max-w-4xl',
            '5xl' => 'max-w-5xl',
            '6xl' => 'max-w-6xl',
            '7xl' => 'max-w-7xl',
        ][$maxWidth] ?? 'max-w-xl';
@endphp

<div
    style="display: none;"
    x-data="synapseModal({{ json_encode($name) }})"
    @open-modal-{{ $name }}.window="open()"
    @close-modal-{{ $name }}.window="close()"
    @keydown.escape.window="show && close()"
    x-show="show"
    x-cloak
    {{ $attributes }}
>
    <!-- Backdrop -->
    <div
        class="syn-modal-backdrop"
        x-show="show"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="close()"
    ></div>

    <!-- Modal Container -->
    <div
        class="syn-modal-container"
        x-show="show"
        @click.self="close()"
    >
        <div
            class="syn-modal-panel {{ $maxWidthClass }}"
            @if ($title) role="dialog" aria-modal="true" aria-labelledby="modal-{{ $name }}-title" @endif
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            @click.stop
        >
            @if ($title)
                <div class="syn-modal-header">
                    <h3 class="syn-modal-title" id="modal-{{ $name }}-title">{{ $title }}</h3>
                    <button class="syn-modal-close" type="button" @click="$dispatch('close-modal-{{ $name }}')">
                        <span class="sr-only">{{ __('Close') }}</span><span class="ph ph-x text-xl" aria-hidden="true"></span>
                    </button>
                </div>
            @endif
            {{ $slot }}
            @isset($footer)
                <div class="syn-modal-footer">
                    {{ $footer }}
                </div>
            @endisset
        </div>
    </div>
</div>

