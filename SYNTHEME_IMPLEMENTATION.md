# Laravel + Synapse Syntheme System - Implementation Plan

## 🚀 Quick Reference Checklist

### Phase 1: Core Infrastructure ⏳
- [ ] Create `SynthemeManager` service (`src/Services/SynthemeManager.php`)
- [ ] Create `Syntheme` facade (`src/Facades/Syntheme.php`)
- [ ] Add helper functions to `src/helper.php` (`syntheme()`, `syntheme_asset()`, `syntheme_color()`)
- [ ] Create `Synthemes` config builder (`src/Services/ConfigGenerator/Builder/Synthemes.php`)
- [ ] Register builder in `Builder.php`
- [ ] Update `SynapseServiceProvider.php` (register singleton + facade)
- [ ] Update `SynAppsDiscover.php` (scan `synapps/synthemes/*/syntheme.json`)
- [ ] Test: `syntheme()` helper and `Syntheme::` facade work

### Phase 2: CSS System ⏳
- [ ] Create `SynthemeStyles` component (`src/View/Components/SynthemeStyles.php`)
- [ ] Create component view (`resources/views/components/syntheme-styles.blade.php`)
- [ ] Update `app.blade.php` layout (add `<x-synapse::syntheme-styles />`)
- [ ] Update `layout.blade.php` layout (add `<x-synapse::syntheme-styles />`)
- [ ] Update `guest.blade.php` layout in synapps-auth (add component)
- [ ] Implement `getCssContent()` in SynthemeManager
- [ ] Test: CSS variables injected in page head

### Phase 3: Asset Resolution ⏳
- [ ] Update `SynAppsPatchResources.php` (add Vite syntheme asset copying)
- [ ] Implement `asset()` method in SynthemeManager
- [ ] Update `sidebar.blade.php` (replace `asset()` with `syntheme_asset()`)
- [ ] Update `header.blade.php` (replace `asset()` with `syntheme_asset()`)
- [ ] Test: Run `npm run build` and verify themed assets copied
- [ ] Test: Logos display from syntheme paths with fallback

### Phase 4: Default Syntheme ⏳
- [ ] Create `synapps/synthemes/default/` directory structure
- [ ] Create `default/syntheme.json` with current design tokens
- [ ] Copy `lta.css` → `default/resources/css/syntheme.css`
- [ ] Copy logos to `default/resources/assets/images/logo/`
- [ ] Create `default/src/DefaultSynthemeServiceProvider.php`
- [ ] Create `default/README.md`
- [ ] Run `composer dump-autoload`
- [ ] Test: Application looks identical to before

### Phase 5: Artisan Commands ⏳
- [ ] Create `SynthemeListCommand.php` (list available synthemes)
- [ ] Create `SynthemeActivateCommand.php` (switch active syntheme)
- [ ] Create `SynthemeMakeCommand.php` (create new syntheme boilerplate)
- [ ] Create `SynthemeCloneCommand.php` (clone existing syntheme)
- [ ] Register all commands in `SynapseServiceProvider.php`
- [ ] Test: `php artisan syntheme:list` shows default syntheme
- [ ] Test: `php artisan syntheme:make test-syntheme` creates structure
- [ ] Test: `php artisan syntheme:activate syntheme_default` works
- [ ] Test: `php artisan syntheme:clone default new-syntheme` works

### Phase 6: Example Syntheme ⏳
- [ ] Run `php artisan syntheme:make blue-corporate`
- [ ] Customize `blue-corporate/syntheme.json` (blue color palette)
- [ ] Create custom `blue-corporate/resources/css/syntheme.css`
- [ ] Add custom logos to `blue-corporate/resources/assets/images/logo/`
- [ ] Activate: `php artisan syntheme:activate syntheme_blue_corporate`
- [ ] Run `npm run build`
- [ ] Test: UI shows blue theme colors and custom logos
- [ ] Document in `blue-corporate/README.md`

### Phase 7: Testing ⏳
- [ ] Write unit tests for `SynthemeManager` methods
- [ ] Write unit tests for helper functions
- [ ] Write feature tests for syntheme discovery
- [ ] Write feature tests for syntheme activation
- [ ] Write feature tests for artisan commands
- [ ] Write integration tests for asset resolution
- [ ] Write integration tests for CSS variable generation
- [ ] All tests pass with `./vendor/bin/pest`

### Phase 8: Documentation ⏳
- [ ] Update `packages/synapse/README.md` (add Syntheme System section)
- [ ] Update `packages/synapse/CLAUDE.md` (add syntheme guidelines)
- [ ] Create `SYNTHEME_GUIDE.md` (step-by-step for developers)
- [ ] Create `SYNTHEME_SCHEMA.md` (syntheme.json reference)
- [ ] Add inline code documentation (PHPDoc blocks)
- [ ] Update main project CLAUDE.md with syntheme usage

### ✅ Final Verification
- [ ] Can create new syntheme: `php artisan syntheme:make my-theme`
- [ ] Can list all synthemes: `php artisan syntheme:list`
- [ ] Can activate syntheme: `php artisan syntheme:activate syntheme_my_theme`
- [ ] Can clone syntheme: `php artisan syntheme:clone default my-clone`
- [ ] CSS variables injected correctly in all layouts (admin, simple, guest)
- [ ] Logos display from active syntheme with fallback to base assets
- [ ] `Syntheme::color('brand.500')` returns correct value
- [ ] `syntheme_asset('logo')` resolves to correct path
- [ ] `syntheme('typography.font_family')` returns font family
- [ ] Theme switching works without recompilation (CSS variables only)
- [ ] Assets require `npm run build` after theme change
- [ ] All tests pass
- [ ] Documentation complete

---

## Overview

Build a **Foundation-First** developer-focused syntheme system that integrates seamlessly with Synapse's modular architecture. Synthemes will support full customization: colors, typography, assets (logos/images), layouts, and custom scripts.

**Key Design Principles:**
- Synthemes as special package modules (`synmod-syntheme-*`) or directory-based (`synapps/synthemes/`)
- Developer-managed via config files and artisan commands
- Leverage existing Synapse patterns (DbConfig, Builder pattern, module discovery)
- Incremental implementation starting with solid foundation

---

## Requirements Summary

From user clarification:
- **Scope**: Colors, Logos/Images, Layout/Components, Typography, Custom Scripts
- **Control**: Developers via config files (not end-user selectable)
- **Management**: Manual creation following patterns, clone via command, switch via command
- **Priority**: Foundation First - extensible architecture for incremental features

---

## Current Architecture Analysis

### Naming Convention

**Prefix:** `syntheme` (for folders, commands, packages)
- Folders: `synapps/synthemes/`, `synmod-syntheme-*`
- Commands: `syntheme:make`, `syntheme:activate`, etc.
- Config keys: `system.active_syntheme`

### Layouts Requiring Theme Support
1. **Admin Layout** (`synapps/resources/views/layouts/app.blade.php`)
   - Full dashboard with sidebar, header, dark mode toggle
   - Logo references: `assets/images/logo/logo.svg`, `logo-dark.svg`, `logo-icon.svg`

2. **Guest Layout** (`packages/synapps-auth/resources/views/layouts/guest.blade.php`)
   - Minimal HTML structure for login/register
   - Uses same utility classes: `.card`, `.text-primary`, `.btn.primary`

3. **Simple Layout** (`synapps/resources/views/layouts/layout.blade.php`)
   - Clean header/footer, no sidebar

### CSS Foundation
- **Tailwind v4** with `@theme {}` blocks and CSS custom properties
- Design tokens in `synapps/resources/css/lta.css`:
  - Color palettes: Brand, Gray, Orange, Success, Error, Warning, Blue-light (12 shades each)
  - Semantic tokens: `--color-surface`, `--color-primary` (light/dark variants)
  - Typography: Title and theme text sizes
  - Shadows: 7 scales + component-specific
  - Z-index: 6 predefined layers

### Asset Pipeline
- `vite-plugin-static-copy` copies from `synapps/resources/assets/**/*` to `public/assets`
- Also copies from vendor module packages
- Tailwind scans compiled Blade views: `@source '../../storage/framework/views/*.php'`

---

## Implementation Plan

### Phase 1: Core Infrastructure

#### 1.1 Theme Structure

**Theme Package Directory Structure:**
```
vendor/*/synmod-syntheme-{name}/
├── syntheme.json                     # Theme metadata & configuration
├── composer.json                     # Package definition
├── src/
│   ├── {ThemeName}ServiceProvider.php
│   └── View/Components/              # Optional theme-specific components
├── resources/
│   ├── css/
│   │   ├── syntheme.css             # Main theme CSS with @theme {} overrides
│   │   └── custom.css               # Additional custom styles (optional)
│   ├── js/
│   │   └── syntheme.js              # Theme-specific JavaScript (optional)
│   ├── assets/
│   │   ├── images/
│   │   │   └── logo/
│   │   │       ├── logo.svg
│   │   │       ├── logo-dark.svg
│   │   │       ├── logo-icon.svg
│   │   │       └── auth-logo.svg    # Optional auth page logo
│   │   ├── fonts/                   # Custom fonts (optional)
│   │   └── favicons/                # Favicon files (optional)
│   └── views/                       # Optional view overrides
│       ├── layouts/
│       └── partials/
└── README.md
```

**Directory Themes** (for development): `synapps/synthemes/{syntheme-name}/`
- Same structure as package themes
- Namespace: `SynApps\Synthemes\{ThemeName}\`

#### 1.2 Theme Configuration Schema (syntheme.json)

```json
{
    "id": "syntheme_blue_corporate",
    "type": "syntheme",
    "name": "Blue Corporate Theme",
    "description": "Professional blue theme for corporate applications",
    "version": "1.0.0",
    "author": "Your Name",
    "author_email": "your.email@example.com",

    "colors": {
        "brand": {
            "25": "#f5f8ff",
            "50": "#eff4ff",
            "100": "#dbe4fe",
            "200": "#bfdbfe",
            "300": "#93c5fd",
            "400": "#60a5fa",
            "500": "#3b82f6",
            "600": "#2563eb",
            "700": "#1d4ed8",
            "800": "#1e40af",
            "900": "#1e3a8a",
            "950": "#172554"
        },
        "semantic": {
            "light": {
                "surface": "var(--color-white)",
                "primary": "var(--color-brand-600)",
                "text": "var(--color-gray-900)",
                "border": "var(--color-gray-200)"
            },
            "dark": {
                "surface": "var(--color-gray-900)",
                "primary": "var(--color-brand-500)",
                "text": "var(--color-white)",
                "border": "var(--color-gray-700)"
            }
        }
    },

    "typography": {
        "font_family": "Inter, sans-serif",
        "font_url": "https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap",
        "heading_font": "Inter, sans-serif"
    },

    "assets": {
        "logo": "assets/images/logo/logo.svg",
        "logo_dark": "assets/images/logo/logo-dark.svg",
        "logo_icon": "assets/images/logo/logo-icon.svg",
        "auth_logo": "assets/images/logo/auth-logo.svg",
        "favicon": "assets/favicons/favicon.ico"
    },

    "scripts": {
        "custom_css": null,
        "custom_js": null
    },

    "view_overrides": {},

    "load_priority": 5
}
```

**Active Theme Storage:** `dbconf('system.active_syntheme', 'default')`

---

### Phase 2: Service Layer

#### 2.1 SynthemeManager Service

**File:** `/media/adods/Data/Herd/synapse/packages/synapse/src/Services/SynthemeManager.php`

**Key Methods:**
```php
// Get active theme ID from DbConfig
public function getActiveThemeId(): string

// Set active theme, persist to DbConfig
public function setActiveTheme(string $id): void

// Get theme configuration value
public function get(string $key, mixed $default = null): mixed

// Get color value with dot notation: 'brand.500', 'semantic.light.primary'
public function color(string $colorKey): ?string

// Resolve themed asset with fallback
public function asset(string $assetKey, ?string $fallback = null): string

// Generate CSS content from theme colors/typography
public function getCssContent(): string

// Get view override path if exists
public function getViewOverride(string $viewPath): ?string

// Get all available themes
public function getAvailableThemes(): array

// Get current theme config
public function getThemeConfig(): array
```

#### 2.2 Facade

**File:** `/media/adods/Data/Herd/synapse/packages/synapse/src/Facades/Syntheme.php`

```php
<?php
namespace VmEngine\Synapse\Facades;

use Illuminate\Support\Facades\Facade;

class Syntheme extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'syntheme';
    }
}
```

**Usage:**
```php
Syntheme::color('brand.500');
Syntheme::asset('logo');
Syntheme::get('typography.font_family');
```

#### 2.3 Helper Functions

**File:** `/media/adods/Data/Herd/synapse/packages/synapse/src/helper.php` (append)

```php
if (!function_exists('syntheme')) {
    function syntheme(?string $key = null, mixed $default = null): mixed
    {
        $manager = app('syntheme');
        return $key ? $manager->get($key, $default) : $manager;
    }
}

if (!function_exists('syntheme_asset')) {
    function syntheme_asset(string $assetKey, ?string $fallback = null): string
    {
        return app('syntheme')->asset($assetKey, $fallback);
    }
}

if (!function_exists('syntheme_color')) {
    function syntheme_color(string $colorKey): ?string
    {
        return app('syntheme')->color($colorKey);
    }
}
```

**Usage in Blade:**
```blade
<img src="{{ syntheme_asset('logo') }}" />
<div style="color: {{ syntheme_color('brand.500') }}">
{{ syntheme('typography.font_family') }}
```

#### 2.4 Service Provider Integration

**File:** `/media/adods/Data/Herd/synapse/packages/synapse/src/SynapseServiceProvider.php`

```php
// In register() method:
$this->app->singleton('syntheme', function ($app) {
    return new \VmEngine\Synapse\Services\SynthemeManager();
});

// In boot() method - register facade alias
$loader = \Illuminate\Foundation\AliasLoader::getInstance();
$loader->alias('Syntheme', \VmEngine\Synapse\Facades\Syntheme::class);
```

---

### Phase 3: Theme Discovery & Registration

#### 3.1 Synthemes Builder

**File:** `/media/adods/Data/Herd/synapse/packages/synapse/src/Services/ConfigGenerator/Builder/Synthemes.php`

**Lifecycle Implementation:**
- `init()` - Initialize empty synthemes array
- `preLoop()` - Set up default syntheme if not exists
- `loop($name, $config)` - Filter modules where `"type": "syntheme"`, add to synthemes array
- `finalize()` - Write `synapps/config/compiled/synthemes.json`

#### 3.2 Builder Registration

**File:** `/media/adods/Data/Herd/synapse/packages/synapse/src/Services/ConfigGenerator/Builder.php`

```php
protected $configBuilder = [
    'apps' => Apps::class,
    'system' => System::class,
    'modules' => Modules::class,
    'synthemes' => Synthemes::class,  // NEW
    'binding' => Binding::class,
    // ...
];
```

#### 3.3 Discovery Enhancement

**File:** `/media/adods/Data/Herd/synapse/packages/synapse/src/Console/Commands/SynAppsDiscover.php`

Updates needed:
- Scan `synapps/synthemes/*/syntheme.json` in addition to modules
- Check for `"type": "syntheme"` in module.json files
- Generate PSR-4 autoload for directory synthemes:
  ```json
  "SynApps\\Synthemes\\{ThemeName}\\": "synapps/synthemes/{syntheme-name}/src/"
  ```

---

### Phase 4: Asset Resolution

#### 4.1 Vite Configuration Update

**File:** `/media/adods/Data/Herd/synapse/packages/synapse/src/Console/Commands/SynAppsPatchResources.php`

Update `patchViteConfig()` method to add theme asset copying:

```javascript
viteStaticCopy({
    targets: [
        // Existing: Base synapps assets
        { src: 'synapps/resources/assets/**/*', dest: 'assets' },

        // NEW: Package syntheme assets
        ...glob.sync('vendor/*/synmod-syntheme-*/resources/assets').map(dir => {
            const synthemeName = dir.match(/synmod-syntheme-([^/]+)/)[1];
            return {
                src: `${dir}/**/*`,
                dest: `synthemes/syntheme-${synthemeName}/assets`
            };
        }),

        // NEW: Directory syntheme assets
        ...glob.sync('synapps/synthemes/*/resources/assets').map(dir => {
            const synthemeName = dir.split('/')[2];
            return {
                src: `${dir}/**/*`,
                dest: `synthemes/${synthemeName}/assets`
            };
        }),

        // Existing: Other module assets...
    ]
})
```

#### 4.2 Asset Resolution Logic

**In SynthemeManager::asset():**

1. Look up asset key in syntheme config (`assets.logo` → `assets/images/logo/logo.svg`)
2. Build themed path: `build/synthemes/{syntheme_id}/assets/{relative_path}`
3. Check if file exists, return if found
4. Fallback to base assets: `build/assets/{relative_path}`
5. Use provided fallback parameter
6. Ultimate fallback: Default logo path

---

### Phase 5: CSS Override System

#### 5.1 SynthemeStyles Component

**Component File:** `/media/adods/Data/Herd/synapse/packages/synapse/src/View/Components/SynthemeStyles.php`

```php
<?php
namespace VmEngine\Synapse\View\Components;

use Illuminate\View\Component;

class SynthemeStyles extends Component
{
    public function render()
    {
        return view('synapse::components.syntheme-styles');
    }
}
```

**View File:** `/media/adods/Data/Herd/synapse/packages/synapse/resources/views/components/syntheme-styles.blade.php`

```blade
{{-- Dynamic CSS Variables from Syntheme Config --}}
<style id="syntheme-variables">
{!! app('syntheme')->getCssContent() !!}
</style>

{{-- Custom Syntheme CSS File (if specified) --}}
@if(syntheme('scripts.custom_css'))
    <link rel="stylesheet" href="{{ asset(syntheme('scripts.custom_css')) }}">
@endif

{{-- Custom Font Import (if specified) --}}
@if(syntheme('typography.font_url'))
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="{{ syntheme('typography.font_url') }}" rel="stylesheet">
@endif
```

#### 5.2 Layout Integration

**Files to Update:**
- `/media/adods/Data/Herd/synapse/packages/synapse/stubs/synapps/resources/views/layouts/app.blade.php`
- `/media/adods/Data/Herd/synapse/packages/synapse/stubs/synapps/resources/views/layouts/layout.blade.php`
- `/media/adods/Data/Herd/synapse/packages/synapps-auth/resources/views/layouts/guest.blade.php`

```blade
<head>
    <!-- ... existing meta tags ... -->
    @vite(['resources/css/app.css'])
    <x-synapse::syntheme-styles />  {{-- NEW: Syntheme CSS injection --}}
    @livewireStyles
    @yield('styles')
    @stack('styles')
</head>
```

#### 5.3 Template Asset Updates

**Files to Update:**
- `/media/adods/Data/Herd/synapse/packages/synapse/stubs/synapps/resources/views/layouts/partials/sidebar.blade.php`
- `/media/adods/Data/Herd/synapse/packages/synapse/stubs/synapps/resources/views/layouts/partials/header.blade.php`

Replace hardcoded asset paths:
```blade
{{-- Before --}}
<img src="{{ asset('assets/images/logo/logo.svg') }}" />
<img src="{{ asset('assets/images/logo/logo-dark.svg') }}" />
<img src="{{ asset('assets/images/logo/logo-icon.svg') }}" />

{{-- After --}}
<img src="{{ syntheme_asset('logo') }}" />
<img src="{{ syntheme_asset('logo_dark') }}" />
<img src="{{ syntheme_asset('logo_icon') }}" />
```

#### 5.4 Example Syntheme CSS

**File:** `vendor/*/synmod-syntheme-blue/resources/css/syntheme.css`

```css
@import "tailwindcss";

@theme {
    /* Brand Colors */
    --color-brand-500: #3b82f6;
    --color-brand-600: #2563eb;

    /* Typography */
    --font-outfit: Inter, sans-serif;

    /* Semantic Colors */
    --color-primary: var(--color-brand-600);
}

/* Component Overrides */
@layer components {
    .btn.primary {
        @apply bg-brand-600 hover:bg-brand-700 text-white;
    }
}
```

**CSS Loading Order:**
1. Base Tailwind: `app.css` → `synapps.css` → `lta.css`
2. Dynamic syntheme variables: `<x-synapse::syntheme-styles />`
3. Syntheme custom CSS (if specified in syntheme.json)
4. Page-specific styles

---

### Phase 6: Artisan Commands

#### 6.1 syntheme:make

**File:** `/media/adods/Data/Herd/synapse/packages/synapse/src/Console/Commands/SynthemeMakeCommand.php`

**Signature:** `php artisan syntheme:make {name}`

**Options:**
- `--type=package|directory` (default: directory)
- `--author=` (default: from git config)
- `--email=` (default: from git config)
- `--force` (overwrite if exists)

**Actions:**
1. Prompt for syntheme type if not provided
2. Create directory structure based on type
3. Generate `syntheme.json` with user inputs (name, author, description)
4. Generate ServiceProvider class
5. Copy default assets (placeholder logos)
6. Create base `resources/css/syntheme.css`
7. Create `composer.json` (if package type)
8. Create README.md with documentation
9. Show success message with next steps

#### 6.2 syntheme:clone

**File:** `/media/adods/Data/Herd/synapse/packages/synapse/src/Console/Commands/SynthemeCloneCommand.php`

**Signature:** `php artisan syntheme:clone {source} {name}`

**Options:**
- `--author=` (override author in cloned syntheme)
- `--force` (overwrite if exists)

**Actions:**
1. Validate source syntheme exists
2. Copy entire syntheme directory to new location
3. Update `syntheme.json`: id, name, author
4. Rename ServiceProvider class file and namespace
5. Update namespace in all PHP files
6. Update composer.json package name (if package)
7. Show success message

#### 6.3 syntheme:activate

**File:** `/media/adods/Data/Herd/synapse/packages/synapse/src/Console/Commands/SynthemeActivateCommand.php`

**Signature:** `php artisan syntheme:activate {syntheme}`

**Actions:**
1. Validate syntheme exists in compiled synthemes
2. Call `SynthemeManager::setActiveSyntheme($id)`
3. Persist to DbConfig: `dbconf(['system.active_syntheme' => $id])`
4. Clear view cache: `php artisan view:clear`
5. Prompt: "Syntheme activated. Run 'npm run build' to compile assets."

#### 6.4 syntheme:list

**File:** `/media/adods/Data/Herd/synapse/packages/synapse/src/Console/Commands/SynthemeListCommand.php`

**Signature:** `php artisan syntheme:list`

**Output:** Table format
```
┌─────────────────────────┬─────────────────────────┬─────────┬──────────┬────────┐
│ ID                      │ Name                    │ Version │ Type     │ Active │
├─────────────────────────┼─────────────────────────┼─────────┼──────────┼────────┤
│ syntheme_default        │ Default Syntheme        │ 1.0.0   │ built-in │   ✓    │
│ syntheme_blue_corporate │ Blue Corporate Theme    │ 1.0.0   │ package  │        │
│ syntheme_dark_minimal   │ Dark Minimal Theme      │ 1.2.0   │ directory│        │
└─────────────────────────┴─────────────────────────┴─────────┴──────────┴────────┘
```

#### 6.5 Command Registration

**File:** `/media/adods/Data/Herd/synapse/packages/synapse/src/SynapseServiceProvider.php`

```php
protected $commands = [
    // ... existing commands ...
    \VmEngine\Synapse\Console\Commands\SynthemeMakeCommand::class,
    \VmEngine\Synapse\Console\Commands\SynthemeCloneCommand::class,
    \VmEngine\Synapse\Console\Commands\SynthemeActivateCommand::class,
    \VmEngine\Synapse\Console\Commands\SynthemeListCommand::class,
];
```

---

## Critical Files Summary

### New Files to Create (Priority Order)

1. **src/Services/SynthemeManager.php** - Core service (CRITICAL)
2. **src/Services/ConfigGenerator/Builder/Synthemes.php** - Syntheme discovery builder
3. **src/Facades/Syntheme.php** - Facade for clean API
4. **src/View/Components/SynthemeStyles.php** - CSS injection component
5. **resources/views/components/syntheme-styles.blade.php** - Component view
6. **src/Console/Commands/SynthemeMakeCommand.php** - Create new synthemes
7. **src/Console/Commands/SynthemeCloneCommand.php** - Clone existing synthemes
8. **src/Console/Commands/SynthemeActivateCommand.php** - Switch active syntheme
9. **src/Console/Commands/SynthemeListCommand.php** - List available synthemes

### Files to Modify

1. **src/SynapseServiceProvider.php** - Register syntheme singleton, commands, facade
2. **src/Services/ConfigGenerator/Builder.php** - Add Synthemes builder
3. **src/Console/Commands/SynAppsDiscover.php** - Add syntheme scanning
4. **src/Console/Commands/SynAppsPatchResources.php** - Update Vite config
5. **src/helper.php** - Add syntheme helper functions
6. **stubs/synapps/resources/views/layouts/app.blade.php** - Add SynthemeStyles component
7. **stubs/synapps/resources/views/layouts/layout.blade.php** - Add SynthemeStyles component
8. **stubs/synapps/resources/views/layouts/partials/sidebar.blade.php** - Replace asset() calls
9. **stubs/synapps/resources/views/layouts/partials/header.blade.php** - Replace asset() calls

**Note:** Guest layout in synapps-auth package should also be updated, but can be done after core implementation.

---

## Migration Path

### Step 1: Extract Default Syntheme
1. Create `synapps/synthemes/default/` directory
2. Copy current `lta.css` → `default/resources/css/syntheme.css`
3. Copy logos from `synapps/resources/assets/images/logo/*` → `default/resources/assets/images/logo/*`
4. Create `syntheme.json` with extracted colors and current design tokens
5. Test: Application should look identical

### Step 2: Implement Core Infrastructure
1. Create SynthemeManager service with basic get/set methods
2. Create Synthemes builder for config generation
3. Update SynAppsDiscover to scan for synthemes
4. Add helper functions
5. Register syntheme singleton in service provider
6. Test: `syntheme()` helper and `Syntheme::` facade work

### Step 3: CSS Integration
1. Create SynthemeStyles component
2. Update base layouts to include component
3. Implement `getCssContent()` in SynthemeManager
4. Test: CSS variables injected correctly

### Step 4: Asset Resolution
1. Update Vite config via SynAppsPatchResources
2. Implement `asset()` method in SynthemeManager
3. Update sidebar/header templates
4. Test: Themed logos display correctly

### Step 5: Commands
1. Implement syntheme:list
2. Implement syntheme:activate
3. Implement syntheme:make
4. Implement syntheme:clone
5. Test: Full workflow of creating, listing, and activating synthemes

### Step 6: Example Syntheme
1. Create "Blue Corporate" syntheme using syntheme:make
2. Customize colors and assets
3. Test syntheme switching
4. Document the syntheme creation process

---

## Post-MVP Features (Future Enhancements)

**Not in initial implementation, can be added incrementally:**

1. **View Override System**
   - Custom ThemeViewFinder extending Laravel's ViewFinder
   - Automatic view resolution from theme view directories
   - Fallback chain: theme views → module views → base views

2. **Custom JavaScript Injection**
   - Support for theme-specific JS files
   - Alpine.js component overrides
   - Chart.js/ApexCharts theming

3. **Parent Theme System**
   - Themes can extend other themes
   - Inheritance chain for configs and assets

4. **Admin UI for Theme Management**
   - Visual theme switcher in backend
   - Live preview mode
   - Theme configuration editor

5. **Theme Marketplace/Registry**
   - Publish themes to package registry
   - Install themes via composer
   - Theme update system

6. **Per-User Theme Selection**
   - User preference in database
   - Theme override at runtime
   - User profile theme picker

---

## Testing Strategy

### Unit Tests
- SynthemeManager service methods
- Syntheme config parsing
- Asset resolution logic
- Helper functions

### Feature Tests
- Syntheme discovery and registration
- Syntheme activation and switching
- Artisan commands
- CSS variable generation

### Integration Tests
- Asset copying via Vite
- Layout rendering with synthemes
- Fallback mechanisms

**Test Location:** `/media/adods/Data/Herd/synapse/packages/synapse/tests/`

---

## Success Criteria

### MVP Completion Checklist

- [ ] SynthemeManager service implemented and registered
- [ ] Syntheme facade and helpers working
- [ ] Synthemes builder integrated with config generation
- [ ] Default syntheme extracted and working
- [ ] SynthemeStyles component injecting CSS correctly
- [ ] Asset resolution working (logos display from syntheme)
- [ ] All 4 artisan commands functional
- [ ] Vite config updated for syntheme assets
- [ ] Layouts updated to use syntheme helpers
- [ ] Can create new syntheme with `syntheme:make`
- [ ] Can switch between synthemes with `syntheme:activate`
- [ ] Example "Blue Corporate" syntheme created and tested
- [ ] Documentation written

### Performance Targets
- Syntheme config cached (no filesystem access on each request)
- Asset resolution uses compiled paths
- CSS generation happens once per syntheme activation

---

## Documentation Requirements

1. **README.md in synapse package** - Add Syntheme System section
2. **CLAUDE.md update** - Add syntheme guidelines for AI assistance
3. **Syntheme Creation Guide** - Step-by-step for developers
4. **syntheme.json Schema Reference** - All available options
5. **Example Synthemes** - 2-3 example synthemes with different styles

---

## Estimated Implementation Time

**Week 1-2: Core Infrastructure (MVP Foundation)**
- SynthemeManager service
- Synthemes builder
- Helper functions and facade
- Service provider integration
- Basic discovery

**Week 2-3: Integration (MVP Features)**
- SynthemeStyles component
- Layout updates
- Asset resolution
- Vite configuration
- Default syntheme extraction

**Week 3-4: Developer Tools (MVP Complete)**
- syntheme:make command
- syntheme:clone command
- syntheme:activate command
- syntheme:list command
- Example syntheme creation

**Post-MVP: Enhancements**
- View override system
- Custom JS support
- Admin UI
- Additional example synthemes

---

## Key Design Decisions Rationale

1. **Synthemes as Special Modules** → Leverage existing proven module system
2. **DbConfig for Active Syntheme** → Runtime switching without env changes
3. **CSS Variables Over Build-Time** → Dynamic theming without recompilation
4. **Hybrid Asset Resolution** → Graceful fallback to base assets
5. **Directory + Package Support** → Flexibility for internal and distributed synthemes
6. **"Syntheme" Prefix** → Clear distinction from generic "theme", unique to Synapse
7. **Foundation First** → Solid base for future enhancements

---

## Example: Blue Corporate Syntheme

### syntheme.json
```json
{
    "id": "syntheme_blue_corporate",
    "type": "syntheme",
    "name": "Blue Corporate Theme",
    "description": "Professional blue color scheme for corporate applications",
    "version": "1.0.0",
    "author": "Your Company",

    "colors": {
        "brand": {
            "500": "#3b82f6",
            "600": "#2563eb"
        },
        "semantic": {
            "light": {
                "primary": "var(--color-brand-600)"
            }
        }
    },

    "typography": {
        "font_family": "Inter, sans-serif",
        "font_url": "https://fonts.googleapis.com/css2?family=Inter:wght@100..900"
    },

    "assets": {
        "logo": "assets/images/logo/logo.svg",
        "logo_dark": "assets/images/logo/logo-dark.svg"
    }
}
```

### Usage
```bash
# Create syntheme
php artisan syntheme:make blue-corporate

# Activate syntheme
php artisan syntheme:activate syntheme_blue_corporate

# Build assets
npm run build
```

---

## Notes

- All paths use forward slashes for cross-platform compatibility
- Syntheme IDs should use snake_case and `syntheme_` prefix
- Asset paths in syntheme.json are relative to syntheme's resources/ directory
- CSS custom properties use kebab-case (--color-brand-500)
- Package synthemes require composer.json with proper PSR-4 autoload
- "Syntheme" = Synapse + Theme, unique naming to avoid conflicts
