{{--
    Alert Component

    A flexible alert component with multiple variants and dismissible functionality.

    Props:
    - type (string): Alert type - 'success', 'warning', 'danger'/'error', 'info' (default: 'info')
    - title (string): Optional alert title
    - message (string): Optional alert message (can also use slot for HTML content)
    - dismissible (bool): Whether alert can be closed (default: true)
    - icon (string): Custom FontAwesome icon class (auto-assigned based on type if not provided)
    - border (bool): Show left border accent (default: false)
    - solid (bool): Use solid background instead of light variant (default: false)

    Usage Examples:

    Basic alert with message:
    <x-synapse-alert type="success" message="Operation completed successfully!" />

    Alert with title and message:
    <x-synapse-alert
        type="warning"
        title="Warning"
        message="Please review your changes before submitting."
    />

    Non-dismissible alert:
    <x-synapse-alert
        type="danger"
        :dismissible="false"
        message="This is a critical error that requires attention."
    />

    Alert with custom content (using slot):
    <x-synapse-alert type="info" title="Information">
        <p>You can use <strong>HTML</strong> content here.</p>
        <a href="#" class="underline">Learn more</a>
    </x-synapse-alert>

    Border accent style:
    <x-synapse-alert type="success" :border="true" message="Success with border accent" />

    Solid background style:
    <x-synapse-alert type="info" :solid="true" message="Solid background alert" />
--}}

@php
    $typeClass = in_array($type, ['success', 'warning', 'danger', 'info']) ? $type : 'info';
@endphp

<div
    class="syn-alert syn-{{ $typeClass }} {{ $border ? 'syn-alert-bordered' : '' }} {{ $solid ? 'syn-alert-solid' : '' }}"
    role="alert"
    x-data="{ show: true }"
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 transform scale-95"
    x-transition:enter-end="opacity-100 transform scale-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 transform scale-100"
    x-transition:leave-end="opacity-0 transform scale-95"
    {{ $attributes }}
>
    <div class="syn-alert-body">
        {{-- Icon --}}
        @if ($icon)
            <div class="syn-alert-icon-wrap">
                <i class="{{ $icon }} syn-alert-icon"></i>
            </div>
        @endif

        {{-- Content --}}
        <div class="syn-alert-content">
            @if ($title)
                <h3 class="syn-alert-title">
                    {{ $title }}
                </h3>
            @endif

            @if ($message)
                <p class="syn-alert-message">
                    {{ $message }}
                </p>
            @endif

            @if ($slot->isNotEmpty())
                <div class="syn-alert-message">
                    {{ $slot }}
                </div>
            @endif
        </div>

        {{-- Dismiss Button --}}
        @if ($dismissible)
            <button
                class="syn-alert-close"
                type="button"
                aria-label="Close alert"
                @click="show = false"
            >
                <svg
                    class="h-4 w-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M6 18L18 6M6 6l12 12"
                    />
                </svg>
            </button>
        @endif
    </div>
</div>
