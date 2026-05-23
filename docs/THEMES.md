# Theme System

Synapse provides a powerful theme system that allows you to customize the look and feel of your application without modifying core files. Themes support multiple layouts, CDN resources, and can be easily switched or customized.

## Features

-   **Multiple Layout Support**: Different layouts for app (backend), frontend, and guest (auth) pages
-   **CDN Resource Management**: Load external CSS/JS per layout type
-   **Theme Fallback System**: Graceful fallback to default theme if active theme is missing
-   **Hot Reloading**: Automatic asset compilation with Vite
-   **Designer-Friendly**: Customize themes without PHP knowledge
-   **Alpine.js Integration**: Interactive components out of the box
-   **Dark Mode Support**: Built-in dark mode with system preference detection

---

## Quick Start

### Create a New Theme

```bash
# Create theme from default template
php artisan synapps:make-theme my-theme --copy-from=default

# Activate theme in synapps/config/apps.json
{
  "themes": {
    "active": "my-theme",
    "fallback": "default"
  }
}

# Regenerate config and build assets
php artisan synapps:config
npm run build
```

---

## Theme Structure

```
synapps/resources/themes/my-theme/
├── theme.json              # Theme metadata and CDN resources
├── README.md               # Theme documentation
├── assets/
│   ├── images/
│   │   ├── logo.svg       # Light mode logo
│   │   └── logo-dark.svg  # Dark mode logo
│   └── fonts/             # Custom web fonts (optional)
├── css/
│   ├── theme-base.css     # Shared styles (loaded on every page)
│   ├── theme-app.css      # Backend/admin-only styles
│   ├── theme-frontend.css # Public frontend-only styles
│   └── theme-guest.css    # Auth pages-only styles
├── js/
│   ├── theme-base.js      # Shared Alpine components & utilities (every page)
│   ├── theme-app.js       # Backend-only Alpine components (sidebar, toggles)
│   ├── theme-frontend.js  # Frontend-only JS (scroll behaviour, flyout menus)
│   └── theme-guest.js     # Auth-only JS (password reveal, OTP autofocus)
└── layouts/
    ├── app.blade.php      # Backend/admin layout
    ├── frontend.blade.php # Public website layout
    └── guest.blade.php    # Authentication pages layout
```

### CSS/JS Split Boundary

Each page loads exactly **two** CSS bundles and **two** JS bundles:

| Bundle | Loaded on | Contains |
|--------|-----------|----------|
| `base.css` + `base.js` | **Every page** | Tailwind utilities, Synapse core, `theme-base.*` |
| `app.css` + `app.js` | Backend (`/admin/*`) | `theme-app.*` — sidebar, header, datatable |
| `frontend.css` + `frontend.js` | Public pages | `theme-frontend.*` — CMS nav, editorial typography |
| `guest.css` + `guest.js` | Auth pages | `theme-guest.*` — auth form container, OTP grid |

This keeps auth pages from loading admin styles and vice-versa.

---

## Configuration

### theme.json

Theme metadata and CDN resources configuration.

```json
{
    "id": "my-theme",
    "name": "My Custom Theme",
    "version": "1.0.0",
    "author": "Your Name",
    "description": "A custom theme for my application",
    "framework": "tailwind",
    "tailwind_version": "4.x",
    "alpine_version": "3.x",
    "layouts": ["app", "guest", "frontend"],

    "css": {
        "all": ["https://fonts.googleapis.com/css2?family=Inter"],
        "app": ["https://cdn.example.com/admin-styles.css"],
        "frontend": ["https://cdn.example.com/frontend-styles.css"]
    },

    "js": {
        "all": ["https://cdn.example.com/analytics.js"],
        "app": []
    },

    "headers": {
        "all": ["<meta name=\"theme-color\" content=\"#4F46E5\">"],
        "app": []
    }
}
```

#### Layout Types

-   **all** - Loaded on every page
-   **app** - Backend/admin pages (URL starts with backend prefix from `synapps-auth` config)
-   **guest** - Authentication pages (login, register, forgot password)
-   **frontend** - Public website pages

### apps.json

Application-level theme configuration in `synapps/config/apps.json`:

```json
{
    "themes": {
        "active": "my-theme",
        "fallback": "default",
        "css": {
            "all": [],
            "app": [],
            "frontend": []
        },
        "js": {
            "all": [],
            "app": [],
            "frontend": []
        },
        "headers": {
            "all": [],
            "app": [],
            "frontend": []
        }
    }
}
```

Resources defined in `apps.json` are merged with theme resources. Theme resources from `theme.json` are automatically loaded during config generation.

---

## Theme Service

The `ThemeService` provides methods for working with themes programmatically.

### Available Methods

```php
use VmEngine\Synapse\Services\ThemeService;

$themeService = app('synapse.theme');

// Get active theme name
$theme = $themeService->getActiveTheme();
// → 'my-theme'

// Get current layout type (auto-detected from URL)
$layoutType = $themeService->getCurrentLayoutType();
// → 'app', 'frontend', or 'guest'

// Resolve theme layout with fallback
$view = $themeService->resolveThemeLayout('app');
// → 'theme::app' or 'theme-fallback::app' or 'synapps::components.layouts.app'

// Get theme path
$path = $themeService->getThemePath('css/theme.css');
// → '/path/to/synapps/resources/themes/my-theme/css/theme.css'

// Render CDN styles for specific layout
$styles = $themeService->renderStyles('app');
// → '<link rel="stylesheet" href="...">...'

// Render CDN scripts for specific layout
$scripts = $themeService->renderScripts('app');
// → '<script src="..."></script>...'

// Render CDN headers for specific layout
$headers = $themeService->renderHeaders('app');
// → '<meta name="theme-color" content="...">...'
```

---

## Helper Functions

Synapse provides convenient helper functions for working with themes.

### theme_asset()

Get URL to theme asset file.

```php
theme_asset(string $path, ?string $theme = null): string
```

**Examples:**

```blade
<!-- Logo -->
<img src="{{ theme_asset('images/logo.svg') }}" alt="Logo">

<!-- Dark mode logo -->
<img src="{{ theme_asset('images/logo-dark.svg') }}"
     alt="Logo"
     class="hidden dark:block">

<!-- Custom image from specific theme -->
<img src="{{ theme_asset('images/banner.jpg', 'other-theme') }}" alt="Banner">
```

**Output:**

```
http://localhost/assets/themes/my-theme/images/logo.svg
```

### theme_path()

Get absolute filesystem path to theme directory or file.

```php
theme_path(string $path = '', ?string $theme = null): string
```

**Examples:**

```php
// Get theme root path
$themePath = theme_path();
// → '/var/www/synapps/resources/themes/my-theme'

// Get specific file path
$cssPath = theme_path('css/theme.css');
// → '/var/www/synapps/resources/themes/my-theme/css/theme.css'

// Check if theme file exists
if (file_exists(theme_path('css/custom.css'))) {
    // Theme has custom CSS
}
```

### active_theme()

Get the currently active theme name.

```php
active_theme(): string
```

**Examples:**

```php
$theme = active_theme();
// → 'my-theme'

// Conditional rendering based on theme
@if(active_theme() === 'dark-theme')
    <link rel="stylesheet" href="/extra-dark-styles.css">
@endif
```

### theme_service()

Get the ThemeService instance (alternative to dependency injection).

```php
theme_service(): ThemeService
```

**Examples:**

```php
$theme = theme_service()->getActiveTheme();
$layoutView = theme_service()->resolveThemeLayout('app');
```

---

## Blade Directives

Synapse registers Blade directives for rendering theme resources.

### @themeStyles

Render CDN stylesheet links **and** Vite asset tags for a specific layout type.

```blade
<head>
    @themeStyles('app')
    <!-- or auto-detect layout from URL -->
    @themeStyles()
</head>
```

**Output (example for `app` layout):**

```html
<!-- CDN resources from theme.json -->
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter" />
<link rel="stylesheet" href="https://cdn.example.com/admin-styles.css" />

<!-- Vite-compiled assets (base + layout-specific) -->
<link rel="stylesheet" href="/build/assets/base-[hash].css" />
<link rel="stylesheet" href="/build/assets/app-[hash].css" />
```

Do **not** call `@vite(['resources/css/app.css'])` separately — it is already included.

### @themeScripts

Render CDN script tags **and** Vite asset tags for a specific layout type.

```blade
<body>
    @themeScripts('app')
    <!-- or auto-detect layout from URL -->
    @themeScripts()
</body>
```

**Output (example for `app` layout):**

```html
<!-- CDN resources from theme.json -->
<script src="https://cdn.example.com/analytics.js"></script>

<!-- Vite-compiled assets (base + layout-specific) -->
<script type="module" src="/build/assets/base-[hash].js"></script>
<script type="module" src="/build/assets/app-[hash].js"></script>
```

### @themeHeaders

Render CDN headers (meta tags, etc.) for a specific layout type.

```blade
<head>
    @themeHeaders('app')
    <!-- or auto-detect layout from URL -->
    @themeHeaders()
</head>
```

**Output:**

```html
<meta name="theme-color" content="#4F46E5" />
```

---

## Creating Custom Themes

### Step 1: Generate Theme

```bash
php artisan synapps:make-theme my-theme --copy-from=default
```

This creates a new theme in `synapps/resources/themes/my-theme/` based on the default template.

### Step 2: Customize Theme Metadata

Edit `synapps/resources/themes/my-theme/theme.json`:

```json
{
    "id": "my-theme",
    "name": "My Awesome Theme",
    "version": "1.0.0",
    "author": "Your Name",
    "description": "A custom theme for my application"
}
```

### Step 3: Customize Styles

Theme styles are split across four files. Edit the file that matches the scope of your change:

| File | Edit when you need to… |
|------|------------------------|
| `css/theme-base.css` | Change colors, buttons, cards, modals, badges — anything shared across all layouts |
| `css/theme-app.css` | Style sidebar, admin header, data tables, backend nav |
| `css/theme-frontend.css` | Style public header/footer, CMS menus, editorial typography |
| `css/theme-guest.css` | Style auth forms, login page centering, OTP digit grid |

**Example — theme-base.css:**

```css
@theme {
    /* Custom colors */
    --color-brand-500: oklch(0.55 0.2 240);
    --color-brand-600: oklch(0.45 0.18 240);

    /* Custom spacing */
    --spacing-sidebar-width: 16rem;
    --spacing-header-height: 4rem;
}

/* Shared component styles */
.my-card {
    @apply bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6;
}
```

Do **not** add `@import "tailwindcss"` to these files — Tailwind is already imported in `resources/css/base.css`.

**OKLCH Color System:**

-   **L** (Lightness): 0-1 (0 = black, 1 = white)
-   **C** (Chroma): 0-0.4 (saturation/intensity)
-   **H** (Hue): 0-360 (color angle)

[OKLCH Color Picker Tool](https://oklch.com/)

### Step 4: Add Alpine.js Components

Theme JS is also split by layout. Edit the appropriate file:

| File | Edit when you need to… |
|------|------------------------|
| `js/theme-base.js` | Shared Alpine components: modal, dropdown, tabs, toastr |
| `js/theme-app.js` | Backend Alpine components: sidebar toggle, global search |
| `js/theme-frontend.js` | Frontend JS: scroll behaviour, CMS flyout menus |
| `js/theme-guest.js` | Auth JS: password reveal, OTP autofocus |

**Example — theme-base.js** (shared components):

Edit `js/theme-base.js`:

```javascript
// Custom Alpine.js components
Alpine.data("myCounter", () => ({
    count: 0,
    increment() {
        this.count++;
    },
}));

// Custom utility functions
window.myUtils = {
    formatCurrency(value) {
        return new Intl.NumberFormat("en-US", {
            style: "currency",
            currency: "USD",
        }).format(value);
    },
};
```

### Step 5: Customize Layouts

Edit layout files in `layouts/`. The `@themeStyles()` and `@themeScripts()` directives automatically inject both CDN links **and** Vite-compiled assets (`base.css` + the layout-specific bundle). Do **not** add `@vite()` calls — they are redundant.

**layouts/app.blade.php** - Backend/admin layout

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- Theme CDN headers (meta tags from theme.json) --}}
    @themeHeaders('app')

    {{-- CDN styles + Vite: base.css + app.css --}}
    @themeStyles('app')
    @livewireStyles
</head>
<body>
    <div class="flex h-screen">
        <!-- Sidebar -->
        <aside class="theme-sidebar">
            @include('theme::nav.sidebar')
        </aside>

        <!-- Main content -->
        <main class="flex-1">
            {{ $slot }}
        </main>
    </div>

    {{-- CDN scripts + Vite: base.js + app.js --}}
    @themeScripts('app')
    @livewireScripts
</body>
</html>
```

### Step 6: Update Assets

Replace logo files in `assets/images/`:

-   `logo.svg` - Light mode logo
-   `logo-dark.svg` - Dark mode logo (optional)

### Step 7: Activate Theme

Edit `synapps/config/apps.json`:

```json
{
    "themes": {
        "active": "my-theme",
        "fallback": "default"
    }
}
```

### Step 8: Build Assets

```bash
# Regenerate configuration
php artisan synapps:config

# Build assets
npm run build

# Or use dev mode with hot reload
npm run dev
```

---

## Theme Fallback System

Synapse implements a three-tier fallback system for theme layouts:

1. **Active Theme** - First tries to load from `synapps/resources/themes/{active}/layouts/`
2. **Fallback Theme** - If not found, tries `synapps/resources/themes/{fallback}/layouts/`
3. **Default Layouts** - Finally falls back to Synapse default layouts in `synapps::components.layouts.*`

**Example Resolution:**

```php
// Active theme: 'custom-theme'
// Fallback theme: 'default'
// Requested layout: 'app'

$view = theme_service()->resolveThemeLayout('app');

// Resolution order:
// 1. synapps/resources/themes/custom-theme/layouts/app.blade.php
//    → Returns 'theme::app'
//
// 2. synapps/resources/themes/default/layouts/app.blade.php
//    → Returns 'theme-fallback::app'
//
// 3. vendor/vm-engine/synapse/resources/views/components/layouts/app.blade.php
//    → Returns 'synapps::components.layouts.app'
```

---

## Asset Management

### Vite Entry Points

The project uses **8 Vite entry points** — one `base` bundle shared by all pages plus a layout-specific bundle per page. `synapps:patch-resources` creates and wires all of these automatically.

```
resources/css/base.css       ← Tailwind + Synapse core + theme-base.css (every page)
resources/css/app.css        ← theme-app.css (backend/admin only)
resources/css/frontend.css   ← theme-frontend.css (public pages only)
resources/css/guest.css      ← theme-guest.css (auth pages only)

resources/js/base.js         ← Alpine plugins + theme-base.js + global helpers (every page)
resources/js/app.js          ← theme-app.js (backend/admin only)
resources/js/frontend.js     ← theme-frontend.js (public pages only)
resources/js/guest.js        ← theme-guest.js (auth pages only)
```

**`resources/css/base.css`** is the only entry that imports Tailwind and `@source` directives:

```css
@import url("https://fonts.googleapis.com/css2?family=Outfit:wght@100..900") layer(base);
@import "tailwindcss";
@import '../../synapps/resources/css/synapps-base.css';
@source '../../storage/framework/views/*.php';
@source '../**/*.blade.php';
/* ... remaining @source directives ... */
@theme {
    --font-sans: "Instrument Sans", ui-sans-serif, system-ui, sans-serif;
}
```

**`resources/css/app.css`** is minimal — just the backend-specific import:

```css
@reference "../../synapps/resources/css/tailwind-ref.css";
@import '../../synapps/resources/css/synapps-app.css';
```

### Synapse Intermediary CSS/JS Files

`synapps/resources/css/` and `synapps/resources/js/` contain split intermediary files that import the active theme's split files. These are generated and patched by `synapps:patch-resources`:

```
synapps/resources/css/synapps-base.css     ← imports synapps.css + active theme-base.css
synapps/resources/css/synapps-app.css      ← imports active theme-app.css
synapps/resources/css/synapps-frontend.css ← imports active theme-frontend.css
synapps/resources/css/synapps-guest.css    ← imports active theme-guest.css

synapps/resources/js/synapps-base.js       ← imports synapps.js + active theme-base.js
synapps/resources/js/synapps-app.js        ← imports active theme-app.js
synapps/resources/js/synapps-frontend.js   ← imports active theme-frontend.js
synapps/resources/js/synapps-guest.js      ← imports active theme-guest.js
```

When you run `synapps:patch-resources`, the theme import lines are updated to point at the currently active theme.

### Vite Configuration

`vite.config.js` is patched by `synapps:patch-resources` to include all 8 entry points plus static asset copying:

```javascript
import laravel from "laravel-vite-plugin";
import { viteStaticCopy } from "vite-plugin-static-copy";
import { glob } from "glob";

export default defineConfig(() => ({
    plugins: [
        laravel({
            input: [
                "resources/css/base.css",
                "resources/css/app.css",
                "resources/css/frontend.css",
                "resources/css/guest.css",
                "resources/js/base.js",
                "resources/js/app.js",
                "resources/js/frontend.js",
                "resources/js/guest.js",
            ],
            refresh: true,
        }),
        viteStaticCopy({
            targets: [
                { src: "synapps/resources/assets/**/*", dest: "assets" },
                // ... module asset targets
            ],
        }),
    ],
}));
```

### Public Asset URLs

Theme assets are accessible via:

```
public/assets/themes/{theme-name}/{path}
```

**Examples:**

```
public/assets/themes/default/images/logo.svg
public/assets/themes/default/css/theme.css
public/assets/themes/my-theme/fonts/custom-font.woff2
```

---

## Component Library

### CSS Class Naming Convention

Theme components use a `.theme-*` prefix to avoid conflicts:

-   `.theme-sidebar-*` - Sidebar components
-   `.theme-header-*` - Header components
-   `.theme-card-*` - Card components
-   `.theme-btn-*` - Button variants
-   `.theme-form-*` - Form components
-   `.theme-badge-*` - Badge variants
-   `.theme-table` - Table component

### Common Components

#### Sidebar

```html
<div
    x-data="themeSidebar"
    class="theme-sidebar"
    :class="{ 'collapsed': collapsed }"
>
    <div class="theme-sidebar-logo">
        <img src="{{ theme_asset('images/logo.svg') }}" alt="Logo" />
        <span>App Name</span>
    </div>

    <nav class="theme-sidebar-nav">
        <a href="/dashboard" class="theme-sidebar-nav-item active">
            <i class="fa-solid fa-home"></i>
            <span>Dashboard</span>
        </a>
    </nav>
</div>
```

#### Card

```html
<div class="theme-card">
    <div class="theme-card-header">
        <h3 class="theme-card-title">Card Title</h3>
        <button class="theme-btn-secondary">Action</button>
    </div>
    <div class="theme-card-body">Card content...</div>
</div>
```

#### Buttons

```html
<button class="theme-btn theme-btn-primary">Primary</button>
<button class="theme-btn theme-btn-secondary">Secondary</button>
<button class="theme-btn theme-btn-success">Success</button>
<button class="theme-btn theme-btn-danger">Danger</button>
```

#### Form Input

```html
<div class="theme-form-group">
    <label class="theme-form-label">Email</label>
    <input
        type="email"
        class="theme-form-input"
        placeholder="you@example.com"
    />
    <div class="theme-form-error">This field is required</div>
</div>
```

#### Badges

```html
<span class="theme-badge theme-badge-primary">Primary</span>
<span class="theme-badge theme-badge-success">Active</span>
<span class="theme-badge theme-badge-warning">Pending</span>
<span class="theme-badge theme-badge-danger">Error</span>
```

---

## Alpine.js Components

### Built-in Components

#### themeSidebar

```javascript
Alpine.data("themeSidebar", () => ({
    collapsed: false,
    toggle() {
        this.collapsed = !this.collapsed;
    },
}));
```

#### modal

```javascript
Alpine.data("modal", () => ({
    isOpen: false,
    open() {
        this.isOpen = true;
    },
    close() {
        this.isOpen = false;
    },
}));
```

#### dropdown

```javascript
Alpine.data("dropdown", () => ({
    isOpen: false,
    toggle() {
        this.isOpen = !this.isOpen;
    },
    close() {
        this.isOpen = false;
    },
}));
```

#### toasts

```javascript
Alpine.data("toasts", () => ({
    items: [],
    success(message) {
        this.add("success", message);
    },
    error(message) {
        this.add("error", message);
    },
    add(type, message) {
        const id = Date.now();
        this.items.push({ id, type, message, visible: true });
        setTimeout(() => this.remove(id), 5000);
    },
    remove(id) {
        const index = this.items.findIndex((item) => item.id === id);
        if (index > -1) {
            this.items[index].visible = false;
            setTimeout(() => {
                this.items.splice(index, 1);
            }, 300);
        }
    },
}));
```

---

## Dark Mode Support

### System Preference

Dark mode automatically activates based on system preference:

```css
@media (prefers-color-scheme: dark) {
    body {
        @apply bg-gray-900 text-gray-100;
    }

    .theme-card {
        @apply bg-gray-800 border-gray-700;
    }
}
```

### Manual Toggle

```javascript
Alpine.data("themeToggle", () => ({
    dark: false,
    init() {
        this.dark =
            localStorage.getItem("theme") === "dark" ||
            (!localStorage.getItem("theme") &&
                window.matchMedia("(prefers-color-scheme: dark)").matches);
        this.updateTheme();
    },
    toggle() {
        this.dark = !this.dark;
        this.updateTheme();
    },
    updateTheme() {
        if (this.dark) {
            document.documentElement.classList.add("dark");
            localStorage.setItem("theme", "dark");
        } else {
            document.documentElement.classList.remove("dark");
            localStorage.setItem("theme", "light");
        }
    },
}));
```

**Usage:**

```blade
<div x-data="themeToggle()">
    <button @click="toggle()" class="theme-header-button">
        <i class="fa-solid" :class="dark ? 'fa-moon' : 'fa-sun'"></i>
    </button>
</div>
```

---

## Troubleshooting

### Theme not loading

1. Check theme exists: `synapps/resources/themes/{theme-name}/`
2. Verify `apps.json` configuration:
    ```json
    { "themes": { "active": "my-theme" } }
    ```
3. Regenerate config: `php artisan synapps:config`
4. Clear cache: `php artisan cache:clear`

### Assets not copying

1. Run patch command: `php artisan synapps:patch-resources`
2. Rebuild assets: `npm run build`
3. Check Vite configuration in `vite.config.js`

### Layout not found

Theme layouts use a fallback system. Check:

1. Active theme has layout: `synapps/resources/themes/{active}/layouts/{layout}.blade.php`
2. Fallback theme has layout: `synapps/resources/themes/{fallback}/layouts/{layout}.blade.php`
3. Synapse default exists: `vendor/vm-engine/synapse/resources/views/components/layouts/{layout}.blade.php`

### Styles not applying

1. Check all 4 split CSS files exist in `synapps/resources/themes/{theme}/css/`:
   - `theme-base.css`, `theme-app.css`, `theme-frontend.css`, `theme-guest.css`
2. Verify Tailwind compilation: `npm run build` — expect 8 output assets
3. Check `public/build/manifest.json` contains all 8 entry points (`base.css`, `app.css`, `frontend.css`, `guest.css` + JS equivalents). A stale 2-entry manifest means `npm run build` was not re-run after the split.
4. Confirm layouts do **not** have a manual `@vite()` call — `@themeStyles()` handles Vite injection
5. Check browser console for CSS errors
6. Clear browser cache (Ctrl+Shift+R)

### Alpine.js not working

1. Ensure Alpine.js is included via CDN or npm
2. Check browser console for JavaScript errors
3. Verify `x-data` directive is present
4. Check Alpine component is defined in `js/theme.js`

---

## Best Practices

### Theme Development

1. **Always create themes from existing templates**

    ```bash
    php artisan synapps:make-theme my-theme --copy-from=default
    ```

2. **Use fallback theme for safety**

    ```json
    {
        "themes": {
            "active": "custom-theme",
            "fallback": "default"
        }
    }
    ```

3. **Test all layout types**

    - Backend/admin (`/apps/*` or configured backend prefix)
    - Frontend (public pages)
    - Guest (auth pages)

4. **Maintain theme.json metadata**

    - Keep version updated
    - Document features and components
    - List all external dependencies

5. **Use theme prefixes for CSS classes**
    ```css
    .theme-card {
    } /* Good */
    .card {
    } /* Bad - conflicts possible */
    ```

### Performance

1. **Minimize CDN resources**

    - Only load what's needed per layout
    - Use `all` sparingly for truly global resources

2. **Optimize assets**

    - Compress images
    - Minify custom CSS/JS in production
    - Use modern image formats (WebP, AVIF)

3. **Leverage caching**
    ```bash
    php artisan config:cache
    php artisan view:cache
    ```

### Maintenance

1. **Version your themes**

    - Update `version` in `theme.json`
    - Keep changelog of theme changes

2. **Document customizations**

    - Update theme `README.md`
    - Comment complex CSS/JS

3. **Test after updates**
    - Run `php artisan synapps:config`
    - Build and test: `npm run build`
    - Clear caches: `php artisan cache:clear`

---

## Examples

### Multi-tenant Application

```json
{
    "themes": {
        "active": "{{ $tenant->theme ?? 'default' }}",
        "fallback": "default"
    }
}
```

### Seasonal Themes

```php
// In a service provider
$month = now()->month;
$theme = match ($month) {
    12, 1, 2 => 'winter',
    3, 4, 5 => 'spring',
    6, 7, 8 => 'summer',
    9, 10, 11 => 'autumn',
};

config(['synapps.apps.themes.active' => $theme]);
```

### User-Selectable Themes

```php
// In controller
public function setTheme(Request $request)
{
    $user = $request->user();
    $user->update(['theme' => $request->theme]);

    // Update session
    session(['theme' => $request->theme]);
}

// In middleware
public function handle($request, $next)
{
    $theme = session('theme') ?? Auth::user()?->theme ?? 'default';
    config(['synapps.apps.themes.active' => $theme]);

    return $next($request);
}
```

---

## Related Documentation

-   [Commands](COMMANDS.md) - `synapps:make-theme` command reference
-   [Configuration](CONFIGURATION.md) - Theme configuration details
-   [Helpers](HELPERS.md) - `theme_asset()`, `theme_path()`, `active_theme()` helpers
-   [Services](SERVICES.md) - ThemeService API reference

---

**Need help?** Check the default theme's `README.md` for designer-friendly examples and tutorials.
