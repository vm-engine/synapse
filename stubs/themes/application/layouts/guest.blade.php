<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >
    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    <title>{{ $title ?? config('synapps.apps.title', config('app.name')) }}</title>

    {{-- Theme CDN Headers (fonts etc. — declared in theme.json) --}}
    @themeHeaders('guest')

    {{-- Theme CDN Styles + Vite (base.css + guest.css via ThemeService) --}}
    @themeStyles('guest')
    {{-- Theme CDN Scripts + Vite (base.js + guest.js via ThemeService). Keep them in <head>:
         on wire:navigate Livewire waits only for new <head> scripts before starting Alpine on the
         new page (body scripts are re-run, not awaited), so a layout entry in <body> registers its
         Alpine components too late. Vite emits deferred module scripts, so a full page load still
         runs them after livewire.js. --}}
    @themeScripts('guest')

    @stack('styles')
</head>

<body
    class="font-sans antialiased"
    x-data="applicationThemeShell"
    :class="{ 'dark': darkMode }"
>
    <div class="flex min-h-screen">
        <!-- LEFT: Banner Column (Hidden on mobile, visible on lg+) -->
        <div
            class="from-primary-600 via-primary-700 to-primary-900 relative hidden overflow-hidden bg-gradient-to-br lg:flex lg:w-1/2">
            <!-- Decorative gradient overlay -->
            <div class="from-primary-500/20 absolute inset-0 bg-gradient-to-br to-transparent"></div>

            <!-- Decorative circles -->
            <div class="absolute -left-20 top-20 h-64 w-64 rounded-full bg-white/10 blur-3xl"></div>
            <div class="absolute -right-20 bottom-20 h-96 w-96 rounded-full bg-white/5 blur-3xl"></div>

            <div class="relative z-10 flex w-full flex-col items-center justify-center p-12 text-white">
                {{-- Logo --}}
                <div class="animate-fade-in mb-8">
                    <img
                        class="h-16"
                        src="{{ theme_asset('images/logo-dark.svg') }}"
                        alt="{{ config('synapps.apps.title', config('app.name')) }}"
                    >
                </div>

                {{-- App Name --}}
                <h1 class="animate-fade-in mb-4 text-center text-5xl font-bold">
                    {{ config('synapps.apps.title', config('app.name')) }}
                </h1>

                {{-- Description --}}
                @if (config('synapps.apps.description'))
                    <p class="text-primary-100 animate-fade-in max-w-md text-center text-xl leading-relaxed">
                        {{ config('synapps.apps.description') }}
                    </p>
                @endif

                {{-- Footer Text --}}
                <div class="text-primary-200 mt-auto pt-12 text-center text-sm">
                    &copy; {{ date('Y') }} {{ config('synapps.apps.title', config('app.name')) }}.
                    {{ __('All rights reserved.') }}
                </div>
            </div>
        </div>

        <!-- RIGHT: Form Column -->
        <div
            class="flex flex-1 items-center justify-center bg-gray-50 p-6 transition-colors duration-300 dark:bg-gray-900">
            <div class="w-full max-w-md">
                <!-- Dark Mode Toggle (Top Right) -->
                <div class="mb-6 flex justify-end">
                    <button
                        class="group relative rounded-lg p-2 text-gray-600 transition-colors hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800"
                        type="button"
                        aria-label="{{ __('Toggle dark mode') }}"
                        @click="toggleDarkMode()"
                    >
                        <!-- Sun Icon (Light Mode) -->
                        <svg
                            class="h-6 w-6"
                            x-show="!darkMode"
                            x-transition:enter="transition-transform duration-200"
                            x-transition:enter-start="rotate-90 scale-0"
                            x-transition:enter-end="rotate-0 scale-100"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"
                            />
                        </svg>

                        <!-- Moon Icon (Dark Mode) -->
                        <svg
                            class="h-6 w-6"
                            x-show="darkMode"
                            x-transition:enter="transition-transform duration-200"
                            x-transition:enter-start="-rotate-90 scale-0"
                            x-transition:enter-end="rotate-0 scale-100"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"
                            />
                        </svg>

                        <!-- Tooltip -->
                        <span
                            class="pointer-events-none absolute -bottom-10 right-0 whitespace-nowrap rounded bg-gray-900 px-2 py-1 text-xs text-white opacity-0 transition-opacity group-hover:opacity-100 dark:bg-gray-700"
                        >
                            {{ __('Toggle theme') }}
                        </span>
                    </button>
                </div>

                <!-- Glass Card Wrapper -->
                <div
                    class="glass-strong animate-fade-in rounded-2xl border border-gray-200 p-8 shadow-2xl dark:border-gray-700">
                    <!-- Logo for Mobile -->
                    <div class="mb-6 flex justify-center lg:hidden">
                        <img
                            class="h-12"
                            src="{{ theme_asset('images/logo.svg') }}"
                            alt="{{ config('synapps.apps.title', config('app.name')) }}"
                            x-show="!darkMode"
                        >
                        <img
                            class="h-12"
                            src="{{ theme_asset('images/logo-dark.svg') }}"
                            alt="{{ config('synapps.apps.title', config('app.name')) }}"
                            x-show="darkMode"
                        >
                    </div>

                    <!-- Content Slot -->
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>


    @stack('scripts')
</body>

</html>
