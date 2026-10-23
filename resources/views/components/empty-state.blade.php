{{--
    Empty State Component

    Centered "nothing here" block for lists, tables and panels.

    Props:
    - title (string): Heading, e.g. "No members yet"
    - text (string): Optional explanation
    - icon (string): Icon class (default: 'ph ph-tray')

    Slot: optional action (button or link).

    Usage:
    <x-synapse-empty-state icon="ph ph-users" title="No members yet" text="Add the first member.">
        <a class="btn primary" href="...">New member</a>
    </x-synapse-empty-state>
--}}
<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center px-6 py-12 text-center']) }}>
    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-gray-800 dark:text-gray-500">
        <i class="{{ $icon }} text-2xl" aria-hidden="true"></i>
    </span>
    <h4 class="mt-3 font-semibold text-gray-800 dark:text-gray-200">{{ $title }}</h4>
    @if ($text)
        <p class="mt-1 max-w-sm text-sm text-gray-500 dark:text-gray-400">{{ $text }}</p>
    @endif
    @if ($slot->isNotEmpty())
        <div class="mt-4">{{ $slot }}</div>
    @endif
</div>
