<div x-cloak>
    <div @click="drawerOpen = false" :class="drawerOpen ? 'block' : 'hidden'"
        class="fixed top-0 left-0 w-full h-screen z-39 bg-gray-900/50"></div>
    <div id="drawer-example"
        class="fixed top-0 right-0 z-40 h-screen p-4 overflow-y-auto transition-transform bg-white w-80 dark:bg-gray-800 shadow-[-4px_0px_4px_0px_rgba(0,_0,_0,_0.1)]"
        tabindex="-1" aria-labelledby="drawer-label" :class="drawerOpen ? 'transform-none' : 'translate-x-full'">
        <h5 id="drawer-label"
            class="inline-flex items-center mb-4 text-base font-semibold text-gray-500 dark:text-gray-400">
            <span class="fa-reguler fa-filter mr-2"></span>
            {{ $title ?? '' }}
        </h5>
        <button type="button" @click="drawerOpen = false" aria-controls="drawer-example"
            class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 absolute top-2.5 end-2.5 flex items-center justify-center dark:hover:bg-gray-600 dark:hover:text-white">
            <span class="fa-reguler fa-xmark"></span>
            <span class="sr-only">Close</span>
        </button>

        {{ $slot }}
    </div>
</div>
