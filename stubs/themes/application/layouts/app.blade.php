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

    <title>{{ isset($title) ? $title . ' - ' : '' }}{{ config('app.name', 'Member4VM') }}</title>

    <!-- Theme Headers -->
    @themeHeaders('app')

    <!-- Vite Assets (includes compiled theme CSS/JS) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Theme Styles -->
    @themeStyles('app')

    @stack('styles')
</head>

<body
    class="bg-gray-50 font-sans antialiased transition-colors duration-300 dark:bg-gray-950"
    x-data="synapseAppShell()"
    :class="{ 'dark': darkMode }"
>
    <div class="flex h-screen overflow-hidden">
        <!-- SIDEBAR: Darker, Independently Scrollable -->
        @include('theme::components.sidebar-menu')

        <!-- MAIN CONTENT AREA -->
        <div
            class="flex flex-1 flex-col overflow-hidden transition-all duration-300"
            :class="{
                'lg:ml-72': sidebarOpen && !sidebarCollapsed,
                'lg:ml-20': sidebarOpen && sidebarCollapsed
            }"
        >
            <!-- HEADER -->
            @include('theme::components.header-nav')

            <!-- MAIN CONTENT -->
            <main class="flex-1 overflow-y-auto p-6">
                <div class="mx-auto max-w-7xl">
                    <!-- Page Header (if provided) -->
                    @if (isset($header))
                        <header class="mb-6">
                            {{ $header }}
                        </header>
                    @endif

                    <!-- Main Content Slot -->
                    {{ $slot }}
                </div>
            </main>

            <!-- Footer (Optional) -->
            @if (isset($footer))
                <footer class="border-t border-gray-200 bg-white px-6 py-4 dark:border-gray-800 dark:bg-gray-900">
                    {{ $footer }}
                </footer>
            @endif
        </div>
    </div>

    <!-- Mobile Overlay (for sidebar) -->
    <div
        class="fixed inset-0 z-30 bg-gray-900/50 backdrop-blur-sm lg:hidden"
        style="display: none;"
        x-show="sidebarOpen && window.innerWidth < 1024"
        @click="sidebarOpen = false"
        x-transition:enter="transition-opacity duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    ></div>

    <!-- Toast Notification Container (Global) -->
    <div
        class="toast"
        style="display: none;"
        x-data="toast"
        x-show="show"
        x-transition:enter="transition-transform duration-300"
        x-transition:enter-start="translate-y-full"
        x-transition:enter-end="translate-y-0"
        x-transition:leave="transition-transform duration-300"
        x-transition:leave-start="translate-y-0"
        x-transition:leave-end="translate-y-full"
        :class="{
            'toast-success': type === 'success',
            'toast-warning': type === 'warning',
            'toast-danger': type === 'danger',
            'toast-info': type === 'info'
        }"
    >
        <div class="flex items-start gap-3">
            <!-- Icon -->
            <div class="shrink-0">
                <!-- Success Icon -->
                <svg
                    class="h-6 w-6 text-green-500"
                    x-show="type === 'success'"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                >
                    <path
                        fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                        clip-rule="evenodd"
                    />
                </svg>

                <!-- Warning Icon -->
                <svg
                    class="h-6 w-6 text-yellow-500"
                    x-show="type === 'warning'"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                >
                    <path
                        fill-rule="evenodd"
                        d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                        clip-rule="evenodd"
                    />
                </svg>

                <!-- Danger Icon -->
                <svg
                    class="h-6 w-6 text-red-500"
                    x-show="type === 'danger'"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                >
                    <path
                        fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                        clip-rule="evenodd"
                    />
                </svg>

                <!-- Info Icon -->
                <svg
                    class="h-6 w-6 text-blue-500"
                    x-show="type === 'info'"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                >
                    <path
                        fill-rule="evenodd"
                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                        clip-rule="evenodd"
                    />
                </svg>
            </div>

            <!-- Message -->
            <div class="flex-1">
                <p
                    class="text-sm font-medium text-gray-900 dark:text-gray-100"
                    x-text="message"
                ></p>
            </div>

            <!-- Close Button -->
            <button
                class="shrink-0 text-gray-400 transition-colors hover:text-gray-600 dark:hover:text-gray-200"
                @click="hide()"
            >
                <svg
                    class="h-5 w-5"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                >
                    <path
                        fill-rule="evenodd"
                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                        clip-rule="evenodd"
                    />
                </svg>
            </button>
        </div>
    </div>

    <!-- Theme Scripts -->
    @themeScripts('app')

    @stack('scripts')

    <script nonce="{{ csp_nonce() }}">
        (() => {
            const register = () => {
                Alpine.data('synapseAppShell', () => ({
                    darkMode: Alpine.$persist(false).as('m4vm_dark_mode_app'),
                    sidebarOpen: Alpine.$persist(true).as('m4vm_sidebar_open'),
                    sidebarCollapsed: Alpine.$persist(false).as('m4vm_sidebar_collapsed'),
                    init() {
                        this.darkMode ? document.documentElement.classList.add('dark') : document
                            .documentElement.classList.remove('dark');
                    },
                    toggleDarkMode() {
                        this.darkMode = !this.darkMode;
                        this.darkMode ? document.documentElement.classList.add('dark') : document
                            .documentElement.classList.remove('dark');
                    },
                }));
            };
            document.addEventListener('alpine:init', register);
            if (window.Alpine) {
                register();
            }
        })();
    </script>
</body>

</html>
