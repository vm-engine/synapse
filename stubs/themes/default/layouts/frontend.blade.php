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
        content="{{ $description ?? config('synapps.apps.description') }}"
    >

    <title>{{ $title ?? config('synapps.apps.title', config('app.name')) }}</title>

    {{-- Theme CDN Headers (fonts etc. — declared in theme.json) --}}
    @themeHeaders('frontend')

    {{-- Module head tags: hreflang, Open Graph, canonical… --}}
    @stack('head')

    {{-- Theme CDN Styles + Vite (base.css + frontend.css via ThemeService) --}}
    @themeStyles('frontend')
    {{-- Theme CDN Scripts + Vite (base.js + frontend.js via ThemeService). Keep them in <head>:
         on wire:navigate Livewire waits only for new <head> scripts before starting Alpine on the
         new page (body scripts are re-run, not awaited), so a layout entry in <body> registers its
         Alpine components too late. Vite emits deferred module scripts, so a full page load still
         runs them after livewire.js. --}}
    @themeScripts('frontend')
    @livewireStyles
    @stack('styles')
</head>

<body
    class="fe-body"
    x-data="{ 'darkMode': $persist(false).as('darkMode') }"
    :class="{ 'dark': darkMode === true }"
>
    <div class="fe-layout">

        {{-- Header --}}
        <header
            class="fe-header"
            x-data="frontendHeader"
            :class="headerClass"
            @keydown.escape.window="close"
        >
            <div class="fe-header__inner">

                {{-- Logo --}}
                <a
                    class="fe-logo"
                    href="{{ url('/') }}"
                >
                    {{ config('synapps.apps.title', config('app.name')) }}
                </a>

                {{-- Navigation: modules/host theme push menus, language switcher… here --}}
                <nav
                    class="fe-nav"
                    id="fe-nav"
                    aria-label="{{ __('Main navigation') }}"
                >
                    @stack('nav')

                    {{-- Auth Actions --}}
                    <div class="fe-header__actions">
                        @auth
                            @if (Route::has('backend.home.dashboard'))
                                <a
                                    class="fe-action-link fe-action-link--primary"
                                    href="{{ route('backend.home.dashboard') }}"
                                >
                                    <span class="fa-solid fa-gauge-high fa-sm"></span>
                                    {{ __('Dashboard') }}
                                </a>
                            @endif
                        @else
                            @if (Route::has('auth.login'))
                                <a
                                    class="fe-action-link"
                                    href="{{ route('auth.login') }}"
                                >
                                    {{ __('Sign in') }}
                                </a>
                            @endif
                        @endauth
                    </div>
                </nav>

                {{-- Mobile toggle --}}
                <button
                    class="fe-mobile-toggle btn-icon"
                    type="button"
                    aria-controls="fe-nav"
                    aria-label="{{ __('Toggle navigation') }}"
                    :aria-expanded="ariaExpanded"
                    @click="toggle"
                >
                    <span class="fa-solid fa-bars"></span>
                </button>
            </div>
        </header>

        {{-- Main Content --}}
        <main class="fe-main">
            @includeIf('synauth::components.impersonation-banner')
            {{ $slot }}
        </main>

        {{-- Footer --}}
        <footer class="fe-footer">
            <div class="fe-footer__inner">
                <div class="fe-footer__brand">
                    <span class="fe-footer__logo">{{ config('synapps.apps.title', config('app.name')) }}</span>
                    <p class="fe-footer__tagline">{{ config('synapps.apps.description') }}</p>
                </div>
                <p class="fe-footer__copy">
                    &copy; {{ date('Y') }} {{ config('synapps.apps.title', config('app.name')) }}.
                    {{ __('All rights reserved.') }}
                </p>
            </div>
        </footer>
    </div>

    @livewireScripts
    @stack('scripts')
</body>

</html>
