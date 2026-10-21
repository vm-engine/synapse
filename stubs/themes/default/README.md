# Default Theme (TailAdmin)

Bundled Synapse theme: TailAdmin-based admin (`app`), auth (`guest`) and public website (`frontend`) layouts, Tailwind v4, Alpine.js, class-based dark mode.

## File Structure

```
default/
├── theme.json              # Metadata + CDN css/js/headers per layout
├── assets/images/          # logo.svg, logo-dark.svg, logo/logo-icon.svg
├── css/
│   ├── theme-base.css      # Shared (every page)
│   ├── theme-app.css       # Admin only
│   ├── theme-guest.css     # Auth pages only
│   └── theme-frontend.css  # Public site only (fe-* shell)
├── js/
│   ├── theme-base.js       # Shared Alpine components
│   ├── theme-app.js        # Admin: sidebar, user menu, search, shortcuts
│   ├── theme-guest.js
│   └── theme-frontend.js   # frontendHeader (scroll shadow + mobile menu)
└── layouts/
    ├── app.blade.php
    ├── guest.blade.php
    └── frontend.blade.php
```

The CSS/JS files compile through the `resources/{css,js}/{base,app,guest,frontend}` Vite entries; layouts load them with `@themeStyles('<layout>')` / `@themeScripts('<layout>')`, both in `<head>` (on `wire:navigate` Livewire only waits for `<head>` scripts before starting Alpine). Never add `@vite()` to a layout.

## Colors & Dark Mode

Colors are TailAdmin's design tokens (`brand-*`, `gray-*`, `success-*`, …) defined in `synapps/resources/css/synapse-components.css`. Change them there — redeclaring `--color-brand-*` in this theme silently overrides the real tokens.

Dark mode is class-based: each layout toggles `.dark` from the persisted Alpine `darkMode` value (shared across admin, auth and public pages). Style with Tailwind `dark:` variants.

## theme.json Resources

```json
{
  "css":     { "all": [], "app": [], "guest": [], "frontend": [] },
  "js":      { "all": [], "app": [], "guest": [], "frontend": [] },
  "headers": { "all": ["<link …>"], "app": [], "guest": [], "frontend": [] }
}
```

`css` / `js` take URLs, `headers` takes raw tags; `all` loads on every layout. The Outfit font is declared in `headers.all`. To self-host it instead of loading from Google:

```bash
php artisan synapps:vendor-fonts
npm run build && php artisan synapps:config
```

Run `php artisan synapps:config` after any `theme.json` change.

## Frontend Layout Contract

`layouts/frontend.blade.php` knows nothing about specific modules; pages and modules feed it:

| Input | How |
| --- | --- |
| Page title | `$title` — Livewire `->title('…')` |
| Meta description | `$description` — Livewire `->layoutData(['description' => '…'])`; defaults to `synapps.apps.description` |
| Head tags (hreflang, Open Graph, canonical) | `@push('head')` |
| Header navigation | `@push('nav')` — inline on desktop, inside the mobile menu below `lg` |
| Extra styles / scripts | `@push('styles')` / `@push('scripts')` |

Example from a CMS page view:

```blade
@push('head')
    <x-cms::hreflang />
@endpush

@push('nav')
    <x-cms::menu position="header" />
    <x-cms::language-switcher />
@endpush
```

UI strings are JSON translation keys — `Sign in`, `Dashboard`, `Main navigation`, `Toggle navigation`, `All rights reserved.` — translate them in `lang/{locale}.json`.

## Helpers

```php
theme_asset('images/logo.svg');   // URL under public/assets/themes/{active}/
theme_path('css/theme-base.css'); // filesystem path inside the active theme
active_theme();                   // 'default'
```

## Custom Themes

```bash
php artisan synapps:make-theme my-theme --copy-from=default
# set "themes.active": "my-theme" in synapps/config/apps.json
php artisan synapps:config
npm run build
```

See `packages/synapse/docs/THEMES.md` for the full theming guide.
