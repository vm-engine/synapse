<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta
        http-equiv="X-UA-Compatible"
        content="IE=edge"
    >
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1, shrink-to-fit=no"
    >
    <meta
        name="description"
        content="{{ config('synapps.apps.description') }}"
    >

    <title>{{ $title ?? config('synapps.apps.title', config('app.name')) }}</title>

    {{-- Theme CDN Headers --}}
    @themeHeaders('app')

    <link
        href="https://fonts.googleapis.com"
        rel="preconnect"
    >
    <link
        href="https://fonts.gstatic.com"
        rel="preconnect"
        crossorigin
    >
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap"
        rel="stylesheet"
    >

    {{-- Theme CDN Styles + Vite (base.css + app.css via ThemeService) --}}
    @themeStyles('app')
    @livewireStyles
    @stack('styles')
</head>

<body
    id="page-top"
    x-data="synapseAppShell('{{ synav()->getActiveMenu() }}')"
    :class="{ 'dark bg-gray-900': darkMode === true }"
>
    <script nonce="{{ csp_nonce() }}">
    (() => {
        const register = () => {
            Alpine.data('synapseAppShell', (page) => ({
                page,
                loaded: true,
                darkMode: false,
                stickyMenu: false,
                sidebarToggle: false,
                scrollTop: false,
                init() {
                    this.darkMode = JSON.parse(localStorage.getItem('darkMode'));
                    this.$watch('darkMode', (value) => localStorage.setItem('darkMode', JSON.stringify(value)));
                }
            }));
        };
    document.addEventListener('alpine:init', register);
    if (window.Alpine) {
        register();
    }
    })();
    </script>
    <div class="page-wrapper">
        @include('synapps::components.layouts.partials.sidebar')
        <div class="main-container">
            @include('synapps::components.layouts.partials.overlay')
            @include('synapps::components.layouts.partials.header')

            <main>
                <div class="p-4 mx-auto max-w-(--breakpoint-2xl) md:p-6">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>

    <!-- Toast Notifications - Outside Livewire context -->
    <x-synapse-toastr></x-synapse-toastr>

    {{-- Theme CDN Scripts + Vite (base.js + app.js via ThemeService) --}}
    @themeScripts('app')
    @livewireScripts
    @stack('scripts')
</body>

</html>
