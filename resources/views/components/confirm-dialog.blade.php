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
    x-data="synapseConfirmDialog()"
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
                        class="fa-solid fa-spinner fa-spin"
                        wire:loading
                    ></span>
                    {{ $confirmText }}
                </button>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ csp_nonce() }}">
    (() => {
        const register = () => {
            Alpine.data('synapseConfirmDialog', () => ({
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
                    if (this.wireMethod && this.wireComponent) {
                        const component = Livewire.find(this.wireComponent);
                        if (component && typeof component[this.wireMethod] === 'function') {
                            if (this.wireParams && Array.isArray(this.wireParams)) {
                                component[this.wireMethod](...this.wireParams);
                            } else {
                                component[this.wireMethod]();
                            }
                        }
                    } else if (this.onConfirm && typeof this.onConfirm === 'function') {
                        this.onConfirm();
                    }
                },
                cancel() {
                    if (this.onCancel && typeof this.onCancel === 'function') {
                        this.onCancel();
                    }
                    this.close();
                }
            }));
        };
        document.addEventListener('alpine:init', register);
        if (window.Alpine) {
            register();
        }
    })();
</script>
