@props([
    'name' => 'modal',
    'maxWidth' => 'xl',
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
    x-data="synapseModal()"
    @open-modal-{{ $name }}.window="open()"
    @close-modal-{{ $name }}.window="close()"
    @keydown.escape.window="show && close()"
    x-show="show"
    x-cloak
    {{ $attributes }}
>
    <!-- Backdrop -->
    <div
        class="fixed inset-0 z-50 bg-gray-900/50 dark:bg-gray-900/80"
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
        class="fixed inset-0 z-[51] flex items-center justify-center p-4"
        x-show="show"
        @click.self="close()"
    >
        <div
            class="{{ $maxWidthClass }} relative flex max-h-[calc(100vh-2rem)] w-full flex-col overflow-y-auto rounded-2xl bg-white shadow-xl dark:bg-gray-800"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            @click.stop
        >
            {{ $slot }}
        </div>
    </div>
</div>

<script nonce="{{ csp_nonce() }}">
    (() => {
        const register = () => {
        Alpine.data('synapseModal', () => ({
            show: false,
            open() {
                this.show = true;
            },
            close() {
                if (!this.show) return;
                this.show = false;
                this.$dispatch('closed-modal-{{ $name }}');
            }
        }));
        };
    document.addEventListener('alpine:init', register);
    if (window.Alpine) {
        register();
    }
    })();
</script>
