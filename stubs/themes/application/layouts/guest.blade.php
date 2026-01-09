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

    <title>{{ config('app.name', 'Member4VM') }}</title>

    <!-- Theme Headers -->
    @themeHeaders('guest')

    <!-- Vite Assets (includes compiled theme CSS/JS) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Theme Styles -->
    @themeStyles('guest')

    @stack('styles')
</head>

<body
    x-data="{ darkMode: $persist(false).as('m4vm_dark_mode_guest') }"
    :class="{ 'dark': darkMode }"
    x-init="darkMode ? document.documentElement.classList.add('dark') : document.documentElement.classList.remove('dark')"
    class="font-sans antialiased"
>
    <div class="min-h-screen flex">
        <!-- LEFT: Banner Column (Hidden on mobile, visible on lg+) -->
        <div
            class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-primary-600 via-primary-700 to-primary-900 relative overflow-hidden">
            <!-- Decorative gradient overlay -->
            <div class="absolute inset-0 bg-gradient-to-br from-primary-500/20 to-transparent"></div>

            <!-- Decorative circles -->
            <div class="absolute top-20 -left-20 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
            <div class="absolute bottom-20 -right-20 w-96 h-96 bg-white/5 rounded-full blur-3xl"></div>

            <div class="relative z-10 flex flex-col justify-center items-center p-12 text-white w-full">
                <!-- Logo -->
                <div class="mb-8 animate-fade-in">
                    <img
                        src="{{ theme_asset('images/logo-dark.svg') }}"
                        class="h-16"
                        alt="Member4VM Logo"
                    >
                </div>

                <!-- App Name -->
                <h1 class="text-5xl font-bold mb-4 text-center animate-fade-in">
                    Member4VM
                </h1>

                <!-- Description -->
                <p class="text-xl text-primary-100 text-center max-w-md leading-relaxed mb-12 animate-fade-in">
                    Powerful membership management platform for virtual machine services
                </p>

                <!-- Features List -->
                <ul class="space-y-4 text-lg animate-fade-in">
                    <li class="flex items-center gap-3">
                        <div class="flex-shrink-0 w-8 h-8 bg-success/20 rounded-full flex items-center justify-center">
                            <svg
                                class="w-5 h-5 text-success"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </div>
                        <span>Secure Authentication</span>
                    </li>
                    <li class="flex items-center gap-3">
                        <div class="flex-shrink-0 w-8 h-8 bg-success/20 rounded-full flex items-center justify-center">
                            <svg
                                class="w-5 h-5 text-success"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </div>
                        <span>Real-time Monitoring</span>
                    </li>
                    <li class="flex items-center gap-3">
                        <div class="flex-shrink-0 w-8 h-8 bg-success/20 rounded-full flex items-center justify-center">
                            <svg
                                class="w-5 h-5 text-success"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </div>
                        <span>Advanced Resource Management</span>
                    </li>
                    <li class="flex items-center gap-3">
                        <div class="flex-shrink-0 w-8 h-8 bg-success/20 rounded-full flex items-center justify-center">
                            <svg
                                class="w-5 h-5 text-success"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </div>
                        <span>24/7 Support & Monitoring</span>
                    </li>
                </ul>

                <!-- Footer Text -->
                <div class="mt-auto pt-12 text-center text-primary-200 text-sm">
                    &copy; {{ date('Y') }} Member4VM. All rights reserved.
                </div>
            </div>
        </div>

        <!-- RIGHT: Form Column -->
        <div
            class="flex-1 flex items-center justify-center p-6 bg-gray-50 dark:bg-gray-900 transition-colors duration-300">
            <div class="w-full max-w-md">
                <!-- Dark Mode Toggle (Top Right) -->
                <div class="flex justify-end mb-6">
                    <button
                        @click="darkMode = !darkMode; darkMode ? document.documentElement.classList.add('dark') : document.documentElement.classList.remove('dark')"
                        type="button"
                        class="p-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors group relative"
                        aria-label="Toggle dark mode"
                    >
                        <!-- Sun Icon (Light Mode) -->
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
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"
                            />
                        </svg>

                        <!-- Moon Icon (Dark Mode) -->
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
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"
                            />
                        </svg>

                        <!-- Tooltip -->
                        <span
                            class="absolute -bottom-10 right-0 bg-gray-900 dark:bg-gray-700 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none"
                        >
                            Toggle theme
                        </span>
                    </button>
                </div>

                <!-- Glass Card Wrapper -->
                <div
                    class="glass-strong rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 p-8 animate-fade-in">
                    <!-- Logo for Mobile -->
                    <div class="lg:hidden flex justify-center mb-6">
                        <img
                            x-show="!darkMode"
                            src="{{ theme_asset('images/logo.svg') }}"
                            class="h-12"
                            alt="Member4VM"
                        >
                        <img
                            x-show="darkMode"
                            src="{{ theme_asset('images/logo-dark.svg') }}"
                            class="h-12"
                            alt="Member4VM"
                        >
                    </div>

                    <!-- Content Slot -->
                    {{ $slot }}
                </div>

                <!-- Additional Links (Optional) -->
                <div class="mt-6 text-center text-sm text-gray-600 dark:text-gray-400">
                    <p>
                        Need help?
                        <a
                            href="#"
                            class="text-primary-600 dark:text-primary-400 hover:underline"
                        >Contact Support</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Theme Scripts -->
    @themeScripts('guest')

    @stack('scripts')
</body>

</html>
