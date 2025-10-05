<div x-cloak>
    <div
        class="fixed top-0 left-0 w-full h-screen z-39 bg-gray-900/50"
        @click="drawerOpen = false"
        :class="drawerOpen ? 'block' : 'hidden'"
    ></div>
    <div
        class="fixed top-0 right-0 z-40 h-screen p-4 overflow-y-auto transition-transform bg-white w-80 dark:bg-gray-800 shadow-[-4px_0px_4px_0px_rgba(0,_0,_0,_0.1)]"
        id="drawer-example"
        aria-labelledby="drawer-label"
        tabindex="-1"
        :class="drawerOpen ? 'transform-none' : 'translate-x-full'"
    >
        <h5
            class="inline-flex items-center mb-4 text-base font-semibold text-gray-500 dark:text-gray-400"
            id="drawer-label"
        >
            {!! $title ?? '' !!}
        </h5>
        <button
            class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 absolute top-2.5 end-2.5 flex items-center justify-center dark:hover:bg-gray-600 dark:hover:text-white"
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
