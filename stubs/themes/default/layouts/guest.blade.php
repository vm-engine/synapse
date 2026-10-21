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
    @themeHeaders('guest')

    {{-- Theme CDN Styles + Vite (base.css + guest.css via ThemeService) --}}
    @themeStyles('guest')
    {{-- Theme CDN Scripts + Vite (base.js + guest.js via ThemeService). Keep them in <head>:
         on wire:navigate Livewire waits only for new <head> scripts before starting Alpine on the
         new page (body scripts are re-run, not awaited), so a layout entry in <body> registers its
         Alpine components too late. Vite emits deferred module scripts, so a full page load still
         runs them after livewire.js. --}}
    @themeScripts('guest')
    @livewireStyles
    @stack('styles')
</head>

<body
    x-data="{ 'loaded': true, 'darkMode': $persist(false).as('darkMode') }"
    :class="{ 'dark bg-gray-900': darkMode === true }"
>
    <div class="min-h-screen flex items-center justify-center bg-gray-100 dark:bg-gray-900">
        <div class="w-full max-w-md">
            {{ $slot }}
        </div>
    </div>

    @livewireScripts
    @stack('scripts')
</body>

</html>
