# CLAUDE.md

# Import personal global standards
@~/.claude/CLAUDE.md

---

Package guidance for **vm-engine/synapse** - Laravel modular architecture system.

## ⚠️ Git Policy

**NEVER commit/push without explicit user approval.** Always show changes and wait for user confirmation.

## Overview

**vm-engine/synapse** provides modular architecture for Laravel apps via:
- Directory-based modules (`synapps/modules/`)
- Package-based modules (`synmod-*`, `synmodule-*`, `synapps-*` in vendor)
- Configuration generators with Builder pattern and `env::` prefix support
- Database services (DbConfig, DbLang) for runtime configuration and translations
- Security traits (WithDeleteToken) for secure operations
- Configuration override system for Laravel config files
- Livewire components and helpers
- Automatic route/provider registration

## Artisan Commands Quick Reference

| Command | Purpose | When to Run | Idempotent | Auto-run by Setup |
|---------|---------|-------------|------------|-------------------|
| `synapps:setup` | Initial setup wizard | Once on fresh install | No (overwrites) | N/A |
| `synapps:config` | Regenerate config files | After config changes | Yes | ✅ Yes |
| `synapps:discover` | Module discovery & deps | After module changes | Yes | ✅ Yes |
| `synapps:patch-resources` | Patch JS/CSS/Vite/routes/seeder | After install, if assets missing | Yes | ✅ Yes |
| `synapps:patch-tests` | Configure test discovery | After install, new modules | Yes | ✅ Yes |
| `synapps:link-errors` | Link error views | After custom error pages | Yes | ✅ Yes |
| `synapps:publish-settings` | Publish Settings module | When you need settings module | Yes | No |
| `synapse:db-config` | Manage database configuration | When updating configs | Yes | No |
| `synapse:db-lang` | Manage database translations | When updating translations | Yes | No |

### synapps:setup
Interactive wizard that:
1. Collects project info (app code, name, author, URLs, prefixes)
2. Copies stub directory from `src/stubs/synapps/` to `synapps/`
3. Generates `synapps/config/apps.json`
4. **Automatically runs these setup commands in sequence:**
   - `vendor:publish --tag=synapse-config --force` - Publish config files (`config/synapse.php`, `config/synapps.php`)
   - `synapps:config` - Regenerate SynApps & Synapse configuration files
   - `synapps:link-errors` - Create symlink for error views
   - `synapps:patch-resources` - Patch JS/CSS imports
   - `synapps:patch-tests` - Configure testing infrastructure
   - `synapps:discover` - Discover modules and update dependencies

**No follow-up required** - All configuration is automatically generated

> **Note:** The setup command now handles all initialization automatically, including publishing configuration files. You only need to manually run the individual commands if troubleshooting or have specific requirements.

### synapps:config
Regenerates SynApps and Synapse configuration files by running both configuration generators:
1. **SynAppsConfigGenerator** - Generates `config/synapps.php` using Builder pattern
2. **SynapseConfigGenerator** - Generates `config/synapse.php` from compiled JSON

```bash
php artisan synapps:config
```

**What it does:**
- Reads source configs from `synapps/config/{apps,system,modules}.json` and `.dev.json` overrides
- Executes all config builders (Apps, System, Modules, Binding) through their lifecycle phases
- Generates compiled configs in `synapps/config/compiled/`
- Writes final configs to `config/synapps.php` and `config/synapse.php`

**Run after:**
- Modifying any `synapps/config/*.json` files
- Adding/removing modules
- Changing module configurations
- Updating system or app settings

**Replaces:** `php artisan config:cache` for Synapse-specific configuration regeneration

> **Note:** This command should be used instead of `config:cache` when working with Synapse modular configurations. It ensures all module configs, bindings, and system settings are properly regenerated. Both `synapps:setup` and `synapps:discover` automatically call this command.

### synapps:discover
Critical command that:
1. Scans for `module.json` in `synapps/modules/` and vendor
2. Generates PSR-4 autoload entries (3 per module: App, Seeders, Factories)
3. Aggregates dependencies from all modules
4. Smart merges using semver (only upgrades)
5. Updates `composer.json` and `package.json`
6. Runs `composer dump-autoload` / `composer update` / `npm install` as needed

**Run after:** Creating modules, modifying `module.json`, changing dependencies

### synapps:patch-resources
Patches Laravel resource files and cleans up default Laravel files:
- **resources/js/app.js** - Adds import for `synapps/resources/js/synapps.js`
- **resources/css/app.css** - Adds import for `synapps/resources/css/synapps.css`
- **resources/css/app.css** - Adds @source directives for module Blade/JS files:
  - `synapps/**/*.blade.php` and `synapps/**/*.js`
  - `vendor/vm-engine/*/**/*.blade.php` and `vendor/vm-engine/*/**/*.js`
  - `vendor/*/synmod-*/**/*.blade.php` and `vendor/*/synmod-*/**/*.js`
  - `vendor/*/synapps-*/**/*.blade.php` and `vendor/*/synapps-*/**/*.js`
- **vite.config.js** - Adds `vite-plugin-static-copy` plugin to copy static assets:
  - Imports `vite-plugin-static-copy` and `glob` packages
  - Copies assets from `synapps/resources/assets/**/*` to `public/assets`
  - Copies assets from `vendor/*/synmod-*/resources/assets/**/*` to `public/assets`
  - Copies assets from `vendor/vm-engine/synapps-*/resources/assets/**/*` to `public/assets`
- **routes/web.php** - Comments out default Laravel welcome route (only if no other routes exist)
- **database/seeders/DatabaseSeeder.php** - Comments out default `User::factory()` calls

**Required npm packages:** `vite-plugin-static-copy` and `glob` (automatically included in `synapps:setup` dependencies)

Safe to run multiple times (only patches if not already present).

### synapps:patch-tests
Comprehensive test configuration that patches **4 files**:

**1. tests/Pest.php** - Adds module test paths to `->in()`:
```php
'../synapps/modules/*/tests/Feature'
'../vendor/*/synmod-*/tests/Feature'
'Browser' // for Pest Browser Testing
```

**2. .env.testing** - Creates with in-memory SQLite:
```env
DB_DATABASE=:memory:
CACHE_STORE=array
QUEUE_CONNECTION=sync
```

**3. composer.json** - Updates test script:
```json
"test": "php artisan config:clear && php artisan config:cache && php artisan test --env=testing"
```

**4. phpunit.xml** - Adds 3 test suites (Browser, Modules, Packages) with proper exclusions

**Run test suites:**
```bash
php artisan test --testsuite=Browser   # All Browser tests
php artisan test --testsuite=Modules   # Module tests
php artisan test --testsuite=Packages  # Package tests
```

### synapps:link-errors
Creates symlink/junction from `resources/views/errors` → `synapps/resources/views/errors`
- Tries symlink first, falls back to Windows junction
- Auto-removes existing link/directory
- No admin required on Windows (uses junctions)

---

## Architecture

### Configuration System

**Dual-generator pattern with caching:**

**1. SynapseConfigGenerator** - Generates `synapse.php`:
- `::config()` - **Optimized method** - reads from cached `synapse.json`, generates if missing
- `::generate()` - **Legacy method** - always regenerates configuration
- Scans filesystem for modules
- Builds paths for migrations, seeders, views, languages
- Relies on compiled JSON in `synapps/config/compiled/`

**2. SynAppsConfigGenerator** - Generates `synapps.php` using Builder pattern:
- `::config()` - **Optimized method** - reads from cached `synapps.json`, generates if missing
- `::generate()` - **Legacy method** - always regenerates configuration
- **Apps Builder**: App-level config (`apps.json` + `apps.dev.json`)
- **System Builder**: System config with protected keys (`system.json` + `system.dev.json`)
- **Modules Builder**: Module discovery and merging (`modules.json` + `modules.dev.json`)
- **Binding Builder**: Service container bindings

**Performance Optimization:**
Use `::config()` instead of `::generate()` in `config/synapse.php` and `config/synapps.php`:
```php
// Optimized (reads cached JSON, only generates if missing)
return SynapseConfigGenerator::config();
return SynAppsConfigGenerator::config();

// Legacy (always regenerates - slower)
return SynapseConfigGenerator::generate();
return SynAppsConfigGenerator::generate();
```

**Builder Lifecycle (5 phases):**
1. `init()` - Initialize state, load configs
2. `preLoop()` - Execute before module iteration
3. `loop($name, $config)` - Process each module
4. `postLoop()` - Execute after all modules
5. `finalize()` - Write compiled configs to disk, process `env::` values

**Environment Variables in JSON:**
All JSON config files support `env::` prefix for environment variables:
```json
{
    "app_name": "env::APP_NAME",
    "api_key": "env::API_KEY"
}
```
During `finalize()`, values starting with `env::` are replaced with actual env values.

**Configuration Files:**

*Source:*
- `synapps/config/{apps,system,modules}.json` - Base configs
- `synapps/config/{apps,system,modules}.dev.json` - Dev overrides
- `config.json` - Protection rules

*Compiled:*
- `synapps/config/compiled/{apps,system,modules,binding}.json`
- `synapps/config/compiled/checksum` - Config hash

### Module Discovery Flow

1. Scan for `module.json` in `synapps/modules/` and vendor patterns
2. Mark as `is_package: 1` if in vendor, `0` otherwise
3. Sort by `load_priority` (default: 99, lower loads first)
4. Merge with override configs (respecting protected keys)
5. Write to `synapps/config/compiled/modules.json`

**Protected Module Keys:** `id`, `namespaces`, `git`, `load_priority`, `is_package`

**Allowed Dev Keys:** `mode`, `laravel_key`, `locale`, `fallback_locale`, `public_path`, `public_url`, `userfile_url`, `binding`

### Service Providers (4 total)

**1. SynapseServiceProvider** (main):
- Publishes configs and assets
- Registers commands
- **Auto-registers module ServiceProviders** (directory modules only)
- Pattern: `SynApps\Modules\{ModuleName}\{ModuleName}ServiceProvider`
- Binds aliases/classes/interfaces (`dbconf`, `dblang`, `synav`)
- Loads migrations, seeders, languages
- **Applies config overrides** from `synapps/config/override/`

**2. SynapseLangServiceProvider**: Language files

**3. SynapseRouteServiceProvider**: Route registration
- `routes/web.php` - Standard (URL + name prefixed)
- `routes/web.backend.php` - Backend (auto-auth, `/admin/*`, `backend.` prefix)
- `routes/web.override.php` - Override (no URL prefix, name prefixed)
- `routes/api.php` - API routes

**4. SynapseViewServiceProvider**: Views and components

### Configuration Override System

**Location:** `synapps/config/override/*.php`

Automatically merges PHP config files with Laravel's config files:
- Override files must match Laravel config file names (`app.php`, `database.php`, etc.)
- Deep recursive array merging
- Applied during service provider boot
- Safe - only affects existing config files

Example `synapps/config/override/app.php`:
```php
return [
    'timezone' => 'Asia/Jakarta',
    'locale' => 'id',
];
```
Merged into `config('app')` automatically.

---

## Helper Services

### DbConfig

Database configuration service with automatic caching.

```php
use VmEngine\Synapse\Facades\DbConfig;

// Get config value
$value = DbConfig::get('app', 'maintenance_mode', false);

// Set config value
DbConfig::set('app', 'maintenance_mode', true, 'Maintenance Mode');

// Force refresh from database
$value = DbConfig::get('app', 'maintenance_mode', false, true);

// Using helper function
$value = dbconf('app.maintenance_mode', false);
dbconf(['app.maintenance_mode' => true]);
$value = dbconf('!app.maintenance_mode'); // Force bypass cache
```

**Service binding:** `'dbconf'`
**Database table:** `configs` (group, key, value, label)
**Command:** `php artisan synapse:db-config group.key value --label="Label"`

### DbLang

Multi-language translation service with automatic caching.

```php
use VmEngine\Synapse\Facades\DbLang;

// Get translation for current language
$text = DbLang::get('welcome.title');

// Get translation for specific language
$text = DbLang::get('welcome.title', 'id');

// Set translation
DbLang::set('welcome.title', 'Welcome!', 'Welcome Title');

// Set translation for specific language
DbLang::set('welcome.title', 'Selamat datang', 'Welcome Title', 'id');

// Change current language
DbLang::setLanguage('id');

// Using helper function
$text = ___('welcome.title');
$text = ___('welcome.title', 'id');
```

**Service binding:** `'dblang'`
**Database table:** `languages` (language_code, key, value, label)
**Command:** `php artisan synapse:db-lang key value --label="Label" --language=en`

**Multi-language auto-creation:**
When `synapps.apps.multi_language` is `true`, setting a translation auto-creates empty entries for all `enabled_languages`.

### ThemeService

Theme management service for customizable application themes with multiple layouts and CDN resource support.

```php
use VmEngine\Synapse\Services\ThemeService;

$themeService = app('synapse.theme');

// Get active theme name
$theme = $themeService->getActiveTheme();
// → 'default'

// Get current layout type (auto-detected from URL)
$layoutType = $themeService->getCurrentLayoutType();
// → 'app', 'frontend', or 'guest'

// Resolve theme layout with fallback chain
$view = $themeService->resolveThemeLayout('app');
// → 'theme::app' or 'theme-fallback::app' or 'synapps::components.layouts.app'

// Get theme path
$path = $themeService->getThemePath('css/theme.css');
// → '/path/to/synapps/resources/themes/default/css/theme.css'

// Render CDN styles for specific layout
$styles = $themeService->renderStyles('app');
// → '<link rel="stylesheet" href="...">...'

// Render CDN scripts
$scripts = $themeService->renderScripts('app');
// → '<script src="..."></script>...'

// Render CDN headers
$headers = $themeService->renderHeaders('app');
// → '<meta name="theme-color" content="...">...'
```

**Service binding:** `'synapse.theme'`
**Layout types:** `'app'` (backend), `'frontend'` (public), `'guest'` (auth)
**Theme location:** `synapps/resources/themes/{theme-name}/`
**Command:** `php artisan synapps:make-theme theme-name --copy-from=default`

**Theme Structure:**
```
themes/{name}/
├── theme.json          # Metadata and CDN resources
├── layouts/
│   ├── app.blade.php
│   ├── frontend.blade.php
│   └── guest.blade.php
├── css/theme.css       # Custom Tailwind v4 styles
├── js/theme.js         # Alpine.js components
└── assets/             # Images, fonts, etc.
```

**Fallback Chain:**
1. Active theme layouts (`synapps/resources/themes/{active}/`)
2. Fallback theme layouts (`synapps/resources/themes/{fallback}/`)
3. Synapse default layouts (`synapps::components.layouts.*`)

**Blade Directives:**
- `@themeStyles('app')` - Render CDN stylesheet links
- `@themeScripts('app')` - Render CDN script tags
- `@themeHeaders('app')` - Render CDN headers (meta tags)

**Helper Functions:**
- `theme_asset($path, $theme)` - Get theme asset URL
- `theme_path($path, $theme)` - Get theme filesystem path
- `active_theme()` - Get active theme name
- `theme_service()` - Get ThemeService instance

See [docs/THEMES.md](docs/THEMES.md) for complete theming guide.

### Breadcrumbs
```php
$breadcrumbs = Breadcrumbs::make('Home', '/')
    ->add('Users', '/users')
    ->add('John Doe', '/users/1', 'fa-user');
```

**Methods:** `make()`, `add()`, `hasItems()`, `items()`, `count()`

### SynapseUtils

**listModuleNamespaces($namespaces, $func)** - Scan namespaces for modules
**listModuleResources($namespaces, $path, $func)** - Scan for specific resources
**isSynAppsAutoloaded()** - Check autoload registration

### Helper Functions

**Database:**
- `dbconf($configString, $default, $force)` - Get/set database config. Use `!` prefix to bypass cache
- `___($key, $languageCode)` - Get database translation

**Theme:**
- `theme_asset($path, $theme)` - Get URL to theme asset (e.g., images, fonts)
- `theme_path($path, $theme)` - Get absolute filesystem path to theme file/directory
- `active_theme()` - Get active theme name
- `theme_service()` - Get ThemeService instance

**Path:**
- `synapps_path($path)` - Absolute path to synapps
- `template_assets($folder, $file)` - Template asset URL
- `DS` - Directory separator constant

**Array:**
- `recuresive_array_merge(array $a1, array $a2)` - Deep merge
- `array_is_list(array $arr)` - Check if sequential

**Routing:**
- `is_route($name, $class = 'active')` - Check current route
- `is_route_prefix($prefix, $class = 'active')` - Check route prefix
- `route_api_opt($appsUrl, $apiUrl)` - API route options
- `route_web_opt($appsUrl, $webUrl)` - Web route options

**Pagination:**
- `pagination_format(int $count, int $offset, int $limit)` - Format array
- `pagination_convert_link($url, $limit)` - Convert to offset/limit
- `pagination_generate($param, $path, $view)` - Generate LengthAwarePaginator

**Number:**
- `is_decimal($value)` - Check if has decimals
- `clean_number_format($value, $precision, $decimal, $thousand)` - Format number

---

## Traits

### AutoRegistersComponents

Auto-register Livewire and Blade components for module ServiceProviders without manual registration.

```php
use VmEngine\Synapse\Traits\AutoRegistersComponents;

class ExampleServiceProvider extends ServiceProvider
{
    use AutoRegistersComponents;

    public function register(): void
    {
        // Auto-register all Livewire and Blade components
        $this->registerComponents();
    }
}
```

**Features:**
- Auto-discovers Livewire components from `src/Livewire/`
- Auto-discovers Blade components from `src/View/Components/`
- Automatic prefix detection from namespace
- Supports nested components with kebab-case naming

**Example Registration:**
- `src/Livewire/ExampleForm.php` → `example.example-form`
- `src/Livewire/Components/ButtonSample.php` → `example.components.button-sample`
- `src/View/Components/Alert.php` → `example::alert`

**Methods:**
- `registerComponents()` - Register all components (Livewire + Blade)
- `registerLivewireComponents()` - Register only Livewire components
- `registerBladeComponents()` - Register only Blade components
- `getComponentPrefix()` - Get the auto-detected prefix

**Properties:**
- `$componentPrefix` - Custom prefix (auto-detected if null)
- `$autoRegisterLivewireComponents` - Enable Livewire auto-registration (default: true)
- `$autoRegisterBladeComponents` - Enable Blade auto-registration (default: true)

See [docs/TRAITS.md#autoregisterscomponents](docs/TRAITS.md#autoregisterscomponents) for complete documentation.

---

### WithDeleteToken

Security trait for generating encrypted delete tokens to prevent unauthorized delete operations.

```php
use VmEngine\Synapse\Traits\WithDeleteToken;

class Post extends Model
{
    use WithDeleteToken;
}

// Usage
$post = Post::find(1);
$token = $post->delete_token; // Auto-appended attribute

// Validate in controller/Livewire
$id = Post::validateDeleteToken($request->delete_token);
if ($id === false || $id != $post->id) {
    // Invalid token
}
```

**Methods:**
- `getDeleteTokenAttribute()` - Auto-appended accessor for encrypted token
- `generateDeleteToken($id)` - Generate token for specific ID
- `validateDeleteToken($token)` - Static method, returns ID or false

**Security:**
- Session-bound with unique salt
- Encrypted using Laravel encryption
- Prevents CSRF-like attacks on delete operations

See [docs/TRAITS.md#withdeletetoken](docs/TRAITS.md#withdeletetoken) for complete documentation.

---

## Blade Components

**Form Components:**
- `<x-synapse::checkboxes name="" :options="[]" :model="[]" />`
- `<x-synapse::radio-buttons name="" :options="[]" :model="" />`
- `<x-synapse::select :options="[]" />`
- `<x-synapse::multi-select name="" :options="[]" :model="[]" />`
- `<x-synapse::password />`
- `<x-synapse::currency currency="$" />`
- `<x-synapse::datepicker :flatpickr="{}" />`

**UI Components:**
- `<x-synapse::confirm-dialog title="" message="" confirmText="" />`
- `<x-synapse::drawer title="" />`
- `<x-synapse::toastr />`
- `<x-synapse::alert type="success|warning|danger|info" message="" title="" :dismissible="true" :border="false" :solid="false" />`

**Alert Component (Detailed):**

Auto-assigns icons by type:
- Success: `fa-solid fa-circle-check`
- Warning: `fa-solid fa-triangle-exclamation`
- Danger/Error: `fa-solid fa-circle-exclamation`
- Info: `fa-solid fa-circle-info`

Features: Alpine.js animations, dark mode, slot content, ARIA attributes

```blade
<x-synapse::alert type="success" message="Saved!" />

<x-synapse::alert type="danger" title="Errors" :dismissible="false">
    <ul class="list-disc list-inside">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</x-synapse::alert>
```

---

## IDE Helper Generator

**Script:** `generate-ide-helper.php` (package root)

Generates PHPDoc for facades, models, and helpers.

```bash
# From Laravel root
php vendor/vm-engine/synapse/generate-ide-helper.php

# With additional paths
php vendor/vm-engine/synapse/generate-ide-helper.php \
  --scan-models=synapps/modules/home/Models \
  --scan-helpers=app/helpers.php \
  --output=/custom/path/_ide_helper.php
```

**Auto-scans:**
- `./src` (package models)
- `./app/Models` (Laravel models)
- `./vendor/laravel/framework/.../Auth`
- `./src/helper.php` (package helpers)

**Generates PHPDoc for:** Core facades, Livewire, Eloquent models, helper functions

**Options:** `--scan-models`, `--scan-helpers`, `--output`, `--help`

Output placed in **current working directory** (where command runs).

---

## Testing

**Framework:** PestPHP 3.x with Orchestra Testbench

**Structure:**
```
tests/
├── Feature/
│   ├── Commands/SynAppsCommandsTest.php
│   └── ModuleDiscoveryTest.php
├── Unit/
│   ├── Components/BladeComponentsTest.php
│   ├── Services/
│   │   ├── Helper/BreadcrumbsTest.php
│   │   ├── ConfigGenerator/
│   │   ├── ConfigGeneratorsTest.php
│   │   └── SynapseUtilsTest.php
│   ├── HelperFunctionsTest.php
│   └── ServiceProvidersTest.php
├── Pest.php
└── TestCase.php
```

**Commands:**

> ⚠️ **WARNING:** Using `composer test` may use default config and refresh main database. Always use Pest directly.

```bash
# ✅ RECOMMENDED: Use Pest directly (uses in-memory SQLite)
./vendor/bin/pest                        # All tests
./vendor/bin/pest --coverage            # With coverage
./vendor/bin/pest tests/Unit            # Specific suite
./vendor/bin/pest --filter="breadcrumbs" # Filter

# ⚠️ AVOID: May refresh main database
# composer test
# composer test-coverage
```

**Coverage (400+ tests):**
- Helper functions: 195+ tests
- Blade components: 70+ tests
- Config generators: 51 tests
- Services: 39 tests
- Commands: 9 tests
- Module discovery: 23 tests

**Test Infrastructure:**
- In-memory SQLite for DB tests
- Helper methods in TestCase for module creation/cleanup
- GitHub Actions ready
- Target coverage: ~85%

---

## Development Notes

### When Modifying Config Generators

1. **SynapseConfigGenerator** depends on compiled JSON files
2. **Builder pattern** executes in order: `init` → `preLoop` → `loop` → `postLoop` → `finalize`
3. Always run `php artisan synapps:discover` after modifying modules or dependencies

### Module Configuration Schema

```json
{
    "id": "module_name",
    "id_case": {
        "snake": "module_name",
        "pascal": "ModuleName",
        "slug": "module-name",
        "studly": "moduleName"
    },
    "name": "Display Name",
    "version": "0.1.0",
    "url_prefix": "url-prefix",
    "load_priority": 99,
    "dependencies": {
        "npm": { "main": {}, "dev": {} },
        "composer": { "main": {}, "dev": {} }
    }
}
```

### Module Types

**Directory-based** (`synapps/modules/`):
- Requires PSR-4 in Laravel's `composer.json` (or run `synapps:discover`)
- ServiceProvider auto-registered by SynapseServiceProvider
- Pattern: `SynApps\Modules\{Name}\{Name}ServiceProvider`

**Package-based** (`vendor/*/synmod-*`):
- Composer packages with `synmod-`, `synmodule-`, or `synapps-` prefix
- ServiceProvider via Laravel package discovery
- No manual namespace registration

---

## Dependencies

**Required:**
- Laravel Framework 12.x
- Livewire 3.x
- composer/semver ^3.0

**Dev:**
- pestphp/pest ^3.0
- pestphp/pest-plugin-laravel ^3.0
- orchestra/testbench ^10.0
- laradumps/laradumps ^4.0
