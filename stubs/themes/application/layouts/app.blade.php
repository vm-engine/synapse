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
    @themeHeaders('app')

    {{-- Theme CDN Styles + Vite (base.css + app.css via ThemeService) --}}
    @themeStyles('app')

    @stack('styles')
</head>

<body
    class="bg-gray-50 font-sans antialiased transition-colors duration-300 dark:bg-gray-950"
    x-data="applicationAppShell()"
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
        x-show="showMobileOverlay"
        @click="closeSidebar"
        x-transition:enter="transition-opacity duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    ></div>

    {{-- Toast notifications (listens to `notify` events) --}}
    <x-synapse-toastr></x-synapse-toastr>

    {{-- Theme CDN Scripts + Vite (base.js + app.js via ThemeService) --}}
    @themeScripts('app')

    @stack('scripts')
</body>

</html>
