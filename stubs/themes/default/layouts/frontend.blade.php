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
    @themeHeaders('frontend')

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >
    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >
    <link
        href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&family=Outfit:wght@300;400;500;600&display=swap"
        rel="stylesheet"
    >

    {{-- Theme CDN Styles + Vite (base.css + frontend.css via ThemeService) --}}
    @themeStyles('frontend')
    @livewireStyles
    @stack('styles')
</head>

<body class="fe-body">
    <div class="fe-layout">

        {{-- Header --}}
        <header
            class="fe-header"
            x-data="frontendHeaderScroll()"
            :class="{ 'fe-header--scrolled': scrolled }"
        >
            <div class="fe-header__inner">

                {{-- Logo --}}
                <a
                    class="fe-logo"
                    href="/"
                >
                    {{ config('synapps.apps.title', config('app.name')) }}
                </a>

                {{-- Auth Actions --}}
                <div class="fe-header__actions">
                    @auth
                        @if (Route::has('backend.home.dashboard'))
                            <a
                                class="fe-action-link fe-action-link--primary"
                                href="{{ route('backend.home.dashboard') }}"
                            >
                                <span class="fa-solid fa-gauge-high fa-sm"></span>
                                Dashboard
                            </a>
                        @endif
                    @else
                        @if (Route::has('auth.login'))
                            <a
                                class="fe-action-link"
                                href="{{ route('auth.login') }}"
                            >
                                Sign in
                            </a>
                        @endif
                    @endauth
                </div>

                {{-- Mobile toggle --}}
                <button
                    class="fe-mobile-toggle"
                    type="button"
                    x-data="{ open: false }"
                    @click="open = !open"
                    aria-label="Toggle navigation"
                >
                    <span class="fa-solid fa-bars"></span>
                </button>
            </div>
        </header>

        {{-- Main Content --}}
        <main class="fe-main">
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
                    All rights reserved.
                </p>
            </div>
        </footer>
    </div>

    {{-- Theme CDN Scripts + Vite (base.js + frontend.js via ThemeService) --}}
    @themeScripts('frontend')
    @livewireScripts
    @stack('scripts')

    <script nonce="{{ csp_nonce() }}">
    (() => {
        const register = () => {
            Alpine.data('frontendHeaderScroll', () => ({
                scrolled: false,
                init() {
                    window.addEventListener('scroll', () => {
                        this.scrolled = window.scrollY > 20;
                    });
                }
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
