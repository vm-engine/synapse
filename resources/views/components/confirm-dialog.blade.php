{{--
    Confirmation Dialog Component

    Usage:
    <x-synapse-confirm-dialog />

    Trigger from button:
    <button @click="$dispatch('confirm-dialog', {
        title: 'Delete Item',
        message: 'Are you sure you want to delete this item?',
        onConfirm: () => { $wire.delete(itemId) }
    })">
        Delete
    </button>

    Or with custom properties:
    <button @click="$dispatch('confirm-dialog', {
        title: 'Custom Title',
        message: 'Custom message',
        confirmText: 'Yes, do it',
        cancelText: 'No, cancel',
        confirmColor: 'danger',
        icon: 'fa-solid fa-trash',
        onConfirm: () => { $wire.customAction() }
    })">
        Action
    </button>
--}}

<div
    style="display: none;"
    wire:ignore
    x-data="{
        show: false,
        title: '{{ $title }}',
        message: '{{ $message }}',
        confirmText: '{{ $confirmText }}',
        cancelText: '{{ $cancelText }}',
        confirmColor: '{{ $confirmColor }}',
        icon: '{{ $icon }}',
        onConfirm: null,
        onCancel: null,
        wireMethod: null,
        wireParams: null,
        wireComponent: null,
        open(data) {
            this.title = data.title || '{{ $title }}';
            this.message = data.message || '{{ $message }}';
            this.confirmText = data.confirmText || '{{ $confirmText }}';
            this.cancelText = data.cancelText || '{{ $cancelText }}';
            this.confirmColor = data.confirmColor || '{{ $confirmColor }}';
            this.icon = data.icon || '{{ $icon }}';
            this.onConfirm = data.onConfirm || null;
            this.onCancel = data.onCancel || null;
            this.wireMethod = data.wireMethod || null;
            this.wireParams = data.wireParams || null;
            this.wireComponent = data.wireComponent || null;
            this.show = true;
        },
        close() {
            this.show = false;
        },
        confirm() {
            // Try Livewire wire method first (for paginated content)
            if (this.wireMethod && this.wireComponent) {
                const component = Livewire.find(this.wireComponent);
                if (component && typeof component[this.wireMethod] === 'function') {
                    if (this.wireParams && Array.isArray(this.wireParams)) {
                        component[this.wireMethod](...this.wireParams);
                    } else {
                        component[this.wireMethod]();
                    }
                }
            }
            // Fallback to onConfirm callback
            else if (this.onConfirm && typeof this.onConfirm === 'function') {
                this.onConfirm();
            }
            // this.close();
        },
        cancel() {
            if (this.onCancel && typeof this.onCancel === 'function') {
                this.onCancel();
            }
            this.close();
        }
    }"
    @confirm-dialog.window="open($event.detail)"
    @keydown.escape.window="show && close()"
    x-show="show"
    x-cloak
    x-on:synapse-confirmed.window="close()"
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
    ></div>

    <!-- Dialog Container -->
    <div
        class="fixed inset-0 z-[51] flex items-center justify-center overflow-y-auto p-4"
        x-show="show"
        @click.self="cancel()"
    >
        <div
            class="relative w-full max-w-md rounded-lg bg-white shadow-xl dark:bg-gray-800"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
        >
            <!-- Header -->
            <div class="flex items-start justify-between border-b border-gray-200 p-5 dark:border-gray-700">
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-full"
                        :class="{
                            'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400': confirmColor === 'danger',
                            'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/30 dark:text-yellow-400': confirmColor === 'warning',
                            'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400': confirmColor === 'primary',
                            'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400': confirmColor === 'success'
                        }"
                    >
                        <i
                            class="text-lg"
                            :class="icon"
                        ></i>
                    </div>
                    <h3
                        class="text-lg font-semibold text-gray-900 dark:text-white"
                        x-text="title"
                    ></h3>
                </div>
                <button
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                    type="button"
                    @click="cancel()"
                >
                    <span class="sr-only">Close</span>
                    <svg
                        class="h-5 w-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>

            <!-- Body -->
            <div class="p-5">
                <p
                    class="text-sm text-gray-600 dark:text-gray-300"
                    x-html="message"
                ></p>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-end gap-3 border-t border-gray-200 p-5 dark:border-gray-700">
                <button
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                    type="button"
                    @click="cancel()"
                    x-text="cancelText"
                ></button>
                <button
                    class="rounded-lg px-4 py-2 text-sm font-medium text-white focus:outline-none focus:ring-2 focus:ring-offset-2"
                    type="button"
                    :class="{
                        'bg-red-600 hover:bg-red-700 focus:ring-red-500': confirmColor === 'danger',
                        'bg-yellow-600 hover:bg-yellow-700 focus:ring-yellow-500': confirmColor === 'warning',
                        'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500': confirmColor === 'primary',
                        'bg-green-600 hover:bg-green-700 focus:ring-green-500': confirmColor === 'success'
                    }"
                    @click="confirm()"
                >
                    <span
                        class="fa-solid fa-spinner fa-spin"
                        wire:loading
                    ></span>
                    {{ $confirmText }}
                </button>
            </div>
        </div>
    </div>
</div>
