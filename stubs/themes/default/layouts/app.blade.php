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

    {{-- Theme CDN Headers (fonts etc. — declared in theme.json) --}}
    @themeHeaders('app')

    {{-- Theme CDN Styles + Vite (base.css + app.css via ThemeService) --}}
    @themeStyles('app')
    @livewireStyles
    @stack('styles')
</head>

<body
    id="page-top"
    x-data="{ page: '{{ synav()->getActiveMenu() }}', 'loaded': true, 'darkMode': $persist(false).as('darkMode'), 'stickyMenu': false, 'sidebarToggle': false, 'scrollTop': false }"
    :class="{ 'dark bg-gray-900': darkMode === true }"
>
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
