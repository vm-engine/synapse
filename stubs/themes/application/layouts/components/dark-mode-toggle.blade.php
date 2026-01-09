{{-- Dark Mode Toggle Component --}}
<button
    @click="darkMode = !darkMode; darkMode ? document.documentElement.classList.add('dark') : document.documentElement.classList.remove('dark')"
    type="button"
    class="p-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors group relative"
    aria-label="Toggle dark mode"
>
    {{-- Sun Icon (Light Mode) --}}
    <svg
        x-show="!darkMode"
        x-transition:enter="transition-transform duration-200"
        x-transition:enter-start="rotate-90 scale-0"
        x-transition:enter-end="rotate-0 scale-100"
        class="w-6 h-6"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
    >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
    </svg>

    {{-- Moon Icon (Dark Mode) --}}
    <svg
        x-show="darkMode"
        x-transition:enter="transition-transform duration-200"
        x-transition:enter-start="-rotate-90 scale-0"
        x-transition:enter-end="rotate-0 scale-100"
        class="w-6 h-6"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
    >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
    </svg>

    {{-- Tooltip --}}
    <span class="absolute -bottom-10 right-0 bg-gray-900 dark:bg-gray-700 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none z-50">
        Toggle theme
    </span>
</button>
