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
        icon: 'ph ph-trash',
        onConfirm: () => { $wire.customAction() }
    })">
        Action
    </button>
--}}

<div
    style="display: none;"
    wire:ignore
    x-data="synapseConfirmDialog({{ json_encode(['title' => $title, 'message' => $message, 'confirmText' => $confirmText, 'cancelText' => $cancelText, 'confirmColor' => $confirmColor, 'icon' => $icon]) }})"
    @confirm-dialog.window="open($event.detail)"
    @keydown.escape.window="show && close()"
    x-show="show"
    x-cloak
    x-on:synapse-confirmed.window="close()"
>
    <!-- Backdrop -->
    <div
        class="syn-confirm-backdrop"
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
        class="syn-confirm-container"
        x-show="show"
        @click.self="cancel()"
    >
        <div
            class="syn-confirm-panel"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
        >
            <!-- Header -->
            <div class="syn-confirm-header">
                <div class="syn-confirm-header-left">
                    <div
                        class="syn-confirm-icon"
                        :class="{
                            'syn-confirm-danger': confirmColor === 'danger',
                            'syn-confirm-warning': confirmColor === 'warning',
                            'syn-confirm-primary': confirmColor === 'primary',
                            'syn-confirm-success': confirmColor === 'success'
                        }"
                    >
                        <i
                            class="text-lg"
                            :class="icon"
                        ></i>
                    </div>
                    <h3
                        class="syn-confirm-title"
                        x-text="title"
                    ></h3>
                </div>
                <button
                    class="syn-confirm-close"
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
            <div class="syn-confirm-body">
                <p
                    class="syn-confirm-message"
                    x-text="message"
                ></p>
            </div>

            <!-- Footer -->
            <div class="syn-confirm-footer">
                <button
                    class="syn-confirm-cancel"
                    type="button"
                    @click="cancel()"
                    x-text="cancelText"
                ></button>
                <button
                    class="syn-confirm-submit"
                    type="button"
                    :class="{
                        'syn-confirm-danger': confirmColor === 'danger',
                        'syn-confirm-warning': confirmColor === 'warning',
                        'syn-confirm-primary': confirmColor === 'primary',
                        'syn-confirm-success': confirmColor === 'success'
                    }"
                    @click="confirm()"
                >
                    <span
                        class="ph ph-spinner-gap syn-spin"
                        wire:loading
                    ></span>
                    <span x-text="confirmText">{{ $confirmText }}</span>
                </button>
            </div>
        </div>
    </div>
</div>

