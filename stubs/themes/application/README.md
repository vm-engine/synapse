# Application Theme

Glassmorphism theme for Synapse web applications: dark, collapsible sidebar admin (`app`), split-screen auth (`guest`) and public website (`frontend`) layouts. Tailwind v4, Alpine.js, class-based dark mode.

## File Structure

```
application/
├── theme.json              # Metadata + CDN css/js/headers per layout
├── assets/images/          # logo.svg, logo-dark.svg
├── css/
│   ├── theme-base.css      # @theme tokens (primary-*, status colors), typography, glass utilities
│   ├── theme-app.css       # Sidebar, header, admin page styles
│   ├── theme-guest.css
│   └── theme-frontend.css  # Public site shell (fe-*)
├── js/
│   ├── theme-base.js       # applicationThemeShell (guest/frontend dark mode), dropdown, modal, tabs, collapsible, tooltip, formValidator
│   ├── theme-app.js        # applicationAppShell, applicationSidebarSubmenu, Cmd/Ctrl+B
│   ├── theme-guest.js
│   └── theme-frontend.js   # frontendHeader (scroll shadow + mobile menu)
└── layouts/
    ├── app.blade.php
    ├── guest.blade.php
    ├── frontend.blade.php
    └── components/         # sidebar-menu, header-nav, dark-mode-toggle, card, button, form-input
```

Layouts load CSS/JS with `@themeStyles('<layout>')` / `@themeScripts('<layout>')`. Never add `@vite()` to a layout.

## Colors & Fonts

`css/theme-base.css` `@theme` defines `--color-primary-50…900` (OKLCH, hue 240) and `--color-success|warning|danger|info`. Change the hue to rebrand.

Body text is Open Sans, headings Poppins, loaded from `theme.json` `headers.all`. To self-host them:

```bash
php artisan synapps:vendor-fonts application
npm run build && php artisan synapps:config
```

## Dark Mode

Class-based: `applicationAppShell` (admin) and `applicationThemeShell` (auth + public) set `.dark` on `<html>` from the persisted `darkMode` value. The default theme uses the same key, so the choice carries across layouts and theme switches.

## Admin Header

Global search (`<livewire:synsearch::palette/>`, Cmd/Ctrl+K), notifications (`<livewire:synotif::bell/>` + `<x-synapse::notification-panel/>`), dark mode toggle and user menu. Toasts use `<x-synapse-toastr/>`, so `$this->dispatch('notify', …)` from any module shows up. Cmd/Ctrl+B toggles the sidebar.

## Frontend Layout Contract

| Input | How |
| --- | --- |
| Page title | `$title` — Livewire `->title('…')` |
| Meta description | `$description` — Livewire `->layoutData(['description' => '…'])`; defaults to `synapps.apps.description` |
| Head tags (hreflang, Open Graph, canonical) | `@push('head')` |
| Header navigation | `@push('nav')` — inline on desktop, in the mobile menu below `lg` |
| Extra styles / scripts | `@push('styles')` / `@push('scripts')` |

UI strings are JSON translation keys (`Sign in`, `Dashboard`, `Sign out`, `Your Profile`, `Toggle sidebar`, `All rights reserved.`, …). Translate them in `lang/{locale}.json`.

## Activate

```bash
php artisan synapps:copy-themes application
# set "themes.active": "application" in synapps/config/apps.json
php artisan synapps:config
php artisan synapps:patch layouts
npm run build
```

See `packages/synapse/docs/THEMES.md` for the full theming guide.
