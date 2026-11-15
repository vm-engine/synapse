{{-- A named drawer owns its `drawerOpen` (synapseDrawer) and listens for
     open-drawer-{name} / close-drawer-{name}; an unnamed one reads the
     parent Alpine scope's `drawerOpen`, as before. --}}
<div
    x-cloak
    @if ($name) x-data="synapseDrawer()"
        @open-drawer-{{ $name }}.window="drawerOpen = true"
        @close-drawer-{{ $name }}.window="drawerOpen = false" @endif
    @keydown.escape.window="drawerOpen = false"
>
    <div
        class="syn-drawer-backdrop"
        @click="drawerOpen = false"
        :class="drawerOpen ? 'block' : 'hidden'"
    ></div>
    <div
        {{ $attributes->class('syn-drawer-panel') }}
        id="{{ $drawerId }}"
        role="dialog"
        aria-labelledby="{{ $drawerId }}-title"
        tabindex="-1"
        :class="drawerOpen ? 'transform-none' : 'translate-x-full'"
    >
        <div class="syn-drawer-header">
            {{-- Raw on purpose: callers pass icon markup. Never put user input in it. --}}
            <h5
                class="syn-drawer-title"
                id="{{ $drawerId }}-title"
            >
                {!! $title !!}
            </h5>
            <button
                class="syn-drawer-close"
                type="button"
                aria-controls="{{ $drawerId }}"
                @click="drawerOpen = false"
            >
                <span class="ph ph-x"></span>
                <span class="sr-only">{{ __('Close') }}</span>
            </button>
        </div>

        <div class="syn-drawer-body">
            {{ $slot }}
        </div>

        @isset($footer)
            <div class="syn-drawer-footer">
                {{ $footer }}
            </div>
        @endisset
    </div>
</div>
