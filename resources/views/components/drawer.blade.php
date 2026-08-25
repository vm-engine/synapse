<div x-cloak>
    <div
        class="syn-drawer-backdrop"
        @click="drawerOpen = false"
        :class="drawerOpen ? 'block' : 'hidden'"
    ></div>
    <div
        class="syn-drawer-panel"
        id="drawer-example"
        aria-labelledby="drawer-label"
        tabindex="-1"
        :class="drawerOpen ? 'transform-none' : 'translate-x-full'"
    >
        <h5
            class="syn-drawer-title"
            id="drawer-label"
        >
            {!! $title ?? '' !!}
        </h5>
        <button
            class="syn-drawer-close"
            type="button"
            aria-controls="drawer-example"
            @click="drawerOpen = false"
        >
            <span class="fa-reguler fa-xmark"></span>
            <span class="sr-only">Close</span>
        </button>

        {{ $slot }}
    </div>
</div>
