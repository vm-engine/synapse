{{--
    Badge Component

    A pill-shaped status/label badge with color variants and an optional icon.

    Props:
    - color (string): 'gray', 'brand', 'success', 'warning', 'danger', 'info' (default: 'gray')
    - size (string): 'sm', 'md' (default: 'md')
    - icon (string): Optional icon class — Phosphor (recommended) e.g. 'ph ph-check', or FontAwesome e.g. 'fa-solid fa-check'

    Usage Examples:

    <x-synapse-badge color="success">Active</x-synapse-badge>
    <x-synapse-badge color="danger" size="sm">Overdue</x-synapse-badge>
    <x-synapse-badge color="success" icon="ph ph-check">Verified</x-synapse-badge>
--}}

<span
    class="syn-badge syn-badge-{{ $color }} syn-badge-{{ $size }}"
    {{ $attributes }}
>
    @if ($icon)
        <i class="{{ $icon }} syn-badge-icon"></i>
    @endif
    {{ $slot }}
</span>
