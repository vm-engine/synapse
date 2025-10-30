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
    // Define color classes based on type and style
    $colorClasses = [
        'success' => [
            'bg' => $solid ? 'bg-green-600' : 'bg-green-50 dark:bg-green-900/20',
            'border' => 'border-green-500',
            'text' => $solid ? 'text-white' : 'text-green-800 dark:text-green-200',
            'icon' => $solid ? 'text-white' : 'text-green-600 dark:text-green-400',
            'close' => $solid
                ? 'text-white hover:text-green-100'
                : 'text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-200',
        ],
        'warning' => [
            'bg' => $solid ? 'bg-yellow-600' : 'bg-yellow-50 dark:bg-yellow-900/20',
            'border' => 'border-yellow-500',
            'text' => $solid ? 'text-white' : 'text-yellow-800 dark:text-yellow-200',
            'icon' => $solid ? 'text-white' : 'text-yellow-600 dark:text-yellow-400',
            'close' => $solid
                ? 'text-white hover:text-yellow-100'
                : 'text-yellow-600 hover:text-yellow-800 dark:text-yellow-400 dark:hover:text-yellow-200',
        ],
        'danger' => [
            'bg' => $solid ? 'bg-red-600' : 'bg-red-50 dark:bg-red-900/20',
            'border' => 'border-red-500',
            'text' => $solid ? 'text-white' : 'text-red-800 dark:text-red-200',
            'icon' => $solid ? 'text-white' : 'text-red-600 dark:text-red-400',
            'close' => $solid
                ? 'text-white hover:text-red-100'
                : 'text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-200',
        ],
        'info' => [
            'bg' => $solid ? 'bg-blue-600' : 'bg-blue-50 dark:bg-blue-900/20',
            'border' => 'border-blue-500',
            'text' => $solid ? 'text-white' : 'text-blue-800 dark:text-blue-200',
            'icon' => $solid ? 'text-white' : 'text-blue-600 dark:text-blue-400',
            'close' => $solid
                ? 'text-white hover:text-blue-100'
                : 'text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200',
        ],
    ];

    $colors = $colorClasses[$type] ?? $colorClasses['info'];
    $borderClass = $border ? "border-l-4 {$colors['border']}" : '';
@endphp

<div
    class="relative rounded-lg p-4 {{ $colors['bg'] }} {{ $borderClass }}"
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
    <div class="flex items-start gap-3">
        {{-- Icon --}}
        @if ($icon)
            <div class="flex-shrink-0 mt-0.5">
                <i class="{{ $icon }} {{ $colors['icon'] }} text-lg"></i>
            </div>
        @endif

        {{-- Content --}}
        <div class="flex-1 min-w-0">
            @if ($title)
                <h3 class="font-semibold {{ $colors['text'] }} mb-1">
                    {{ $title }}
                </h3>
            @endif

            @if ($message)
                <p class="{{ $colors['text'] }} text-sm">
                    {{ $message }}
                </p>
            @endif

            @if ($slot->isNotEmpty())
                <div class="{{ $colors['text'] }} text-sm">
                    {{ $slot }}
                </div>
            @endif
        </div>

        {{-- Dismiss Button --}}
        @if ($dismissible)
            <button
                class="flex-shrink-0 inline-flex items-center justify-center w-6 h-6 rounded-md {{ $colors['close'] }} transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-{{ $type === 'danger' ? 'red' : ($type === 'warning' ? 'yellow' : ($type === 'success' ? 'green' : 'blue')) }}-500"
                type="button"
                aria-label="Close alert"
                @click="show = false"
            >
                <svg
                    class="w-4 h-4"
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
