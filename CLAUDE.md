# CLAUDE.md

# Import personal global standards
@~/.claude/CLAUDE.md

# Import project standards
@../../CLAUDE.md

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
- Livewire traits (WithDeleteToken, WithReturnUrl, RemembersQueryParams) for common patterns
- Configuration override system for Laravel config files
- Livewire components and helpers
- Automatic route/provider registration

## Artisan Commands Quick Reference

| Command | Purpose | When to Run | Idempotent | Auto-run by Setup |
|---------|---------|-------------|------------|-------------------|
| `synapps:setup` | Initial setup wizard | Once on fresh install | No (overwrites) | N/A |
| `synapps:config` | Regenerate config files | After config changes | Yes | ✅ Yes |
| `synapps:discover` | Module discovery & deps | After module changes | Yes | ✅ Yes |
| `synapps:patch` | Patch JS/CSS/Vite/routes/seeder/tests | After install, if assets missing | Yes | ✅ Yes |
| `synapps:link-errors` | Link error views | After custom error pages | Yes | ✅ Yes |
| `synapps:copy-stubs` | Copy stub modules into synapps/modules | When you need example module code | Yes | No |
| `synapps:copy-resources` | Re-copy stub layouts/CSS/JS/assets | Refreshing stub resources after an upgrade | Yes | No |
| `synapps:copy-themes` | Copy stub themes | Refreshing bundled themes | Yes | No |
| `synapps:make` | Create a module component (`object module name`, e.g. `livewire`, `--mfc/--sfc/--class`) | Building out a module | Yes | No |
| `synapps:make-theme` | Create new theme from template | Custom themes | Yes | No |
| `synapps:remove-module` | Move a module to `.trash` (or `--permanent`; `--force`/`-n` skips the prompt) and rediscover | Retiring a module | No | No |
| `synapps:reconfig` | Interactively update core `apps.json` fields | Renaming app/changing domains after setup | Yes | No |
| `synapps:vendor-fonts` | Self-host a theme's Google Fonts | Removing third-party font CDN calls | Yes | No |
| `synapps:publish-settings` | Publish Settings module | When you need settings module | Yes | No |
| `synapse:db-config` | Manage database configuration | When updating configs | Yes | No |
| `synapse:db-lang` | Manage database translations | When updating translations | Yes | No |
| `synapse:patch-composer` | Merge composer.local.json | Local dev with path repos | Yes | No |
| `synapse:restore-composer` | Restore original composer.json | After patching | Yes | No |
| `synapse:excel-cleanup` | Mark stale Excel tasks as failed | Scheduled or manual | Yes | No |
| `synapps:searchable-setup` | Enable global search; (re)import nav + module searchables | Once to enable, then after module/language changes | Yes | No |
| `synapps:searchable-forget` | Delete `content:*` searchable rows by source prefix | Module uninstall / cleanup | Yes | No |
| `synapps:upgrade-audit` | Detect (and optionally auto-fix) legacy Synapse patterns | Before/while upgrading vm-engine/synapse | Yes | No |

### synapps:upgrade-audit
Detects legacy `vm-engine/synapse` patterns that need updating before/while upgrading, and optionally auto-fixes the mechanical ones.

```bash
php artisan synapps:upgrade-audit              # report only, writes SYNAPSE_UPGRADE_AUDIT.md
php artisan synapps:upgrade-audit --apply      # also rewrite auto-fixable findings
```

**Options:** `--apply`, `--only=*` (restrict to rule IDs, e.g. `--only=A`), `--force` (skip git-clean check + confirmation), `--output=` (report path)

**Detects:**
- Auto-fixable: Blade `<livewire:module.name>` dot-notation → `::` namespace notation, `Route::livewire()` dotted string targets, removed `registerMfcComponents()`/`registerLivewireComponents()` calls
- Manual-review only (never auto-fixed): Livewire v3 `Livewire.hook()` JS calls (need porting to `Livewire.interceptMessage()`), `Route::get(Component::class)` candidates, `@themeScripts(...)` after `</head>` in a layout (rule F — must be in `<head>` so `wire:navigate` waits for it), Alpine components registered only in an anonymous `alpine:init` callback (rule G — missing after a cross-layout `wire:navigate`)

**If the project is stuck on a version too old to have this command**, copy the self-contained standalone script `vendor/vm-engine/synapse/tools/synapse-upgrade-audit.php` into the project root and run `php synapse-upgrade-audit.php` directly — no autoload/Composer dependency, audit-only. See [UPGRADE.md](UPGRADE.md#before-you-upgrade) and [docs/COMMANDS.md](docs/COMMANDS.md#synappsupgrade-audit).

### synapps:setup
Interactive wizard that:
1. Collects project info (app code, name, author, URLs, prefixes)
2. Copies stub directory from `src/stubs/synapps/` to `synapps/`
3. Generates `synapps/config/apps.json`
4. **Automatically runs these setup commands in sequence:**
   - `vendor:publish --tag=synapse-config --force` - Publish config files (`config/synapse.php`, `config/synapps.php`)
   - `synapps:config` - Regenerate SynApps & Synapse configuration files
   - `synapps:link-errors` - Create symlink for error views
   - `synapps:patch` - Patch JS/CSS imports and configure testing infrastructure
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
- Reads source configs from `synapps/config/{apps,modules}.json` and `.dev.json` overrides
- Executes all config builders (Apps, Modules, Binding) through their lifecycle phases
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
6. Runs `composer update <changed packages> --with-all-dependencies` (never a bare `composer update`), or `composer dump-autoload` when only the autoload map changed, plus `npm install` / `npm run build` when `package.json` changed — all through the `Process` facade, reporting a non-zero exit and failing the command

**Run after:** Creating modules, modifying `module.json`, changing dependencies

### synapps:patch
Patches Laravel resource files, wires per-layout CSS/JS split, and cleans up default Laravel files:

**Creates per-layout entry files (if missing):**
- `resources/css/base.css` — Tailwind + `synapps-base.css` + all `@source` directives
- `resources/css/app.css` — imports `synapps-app.css` only
- `resources/css/frontend.css` — imports `synapps-frontend.css` only
- `resources/css/guest.css` — imports `synapps-guest.css` only
- `resources/js/base.js` — `synapps-base.js` + `withBack` helper + mousedown listener
- `resources/js/app.js` — imports `synapps-app.js` only
- `resources/js/frontend.js` — imports `synapps-frontend.js` only
- `resources/js/guest.js` — imports `synapps-guest.js` only

**Creates Synapse intermediary files (if missing):**
- `synapps/resources/css/synapps-base.css` — imports `synapps.css` + active `theme-base.css`
- `synapps/resources/css/synapps-{app,frontend,guest}.css` — import active theme split files
- `synapps/resources/js/synapps-base.js` — imports `synapps.js` + active `theme-base.js`
- `synapps/resources/js/synapps-{app,frontend,guest}.js` — import active theme split files

**Patches existing files:**
- **theme import lines** in all 8 `synapps-*.css` / `synapps-*.js` — updated to point at the active theme whenever the active theme changes
- **vite.config.js** — expands `input` array to all 8 entry points; adds `vite-plugin-static-copy`:
  - Copies assets from `synapps/resources/assets/**/*` to `public/assets`
  - Copies assets from `vendor/*/synmod-*/resources/assets/**/*` to `public/assets`
  - Copies assets from `vendor/vm-engine/synapps-*/resources/assets/**/*` to `public/assets`
- **routes/web.php** — comments out default Laravel welcome route (only if no other routes exist)
- **database/seeders/DatabaseSeeder.php** — comments out default `User::factory()` calls

**Required npm packages:** `vite-plugin-static-copy` and `glob` (automatically included in `synapps:setup` dependencies)

**Also configures testing infrastructure** (the `tests` object), patching **4 files**:

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

### synapps:reconfig
Interactive updater for core `apps.json` fields (app code, name, author, domains, prefixes, title, description, multi-language, notifications, IP whitelist/blacklist), each defaulting to the current config value.

```bash
php artisan synapps:reconfig
php artisan synapps:reconfig --dev   # write only the changed answers to apps.dev.json, apps.json untouched
```

**Preserves (not reset) on every run:** `themes`, `dependencies`, `csp`, `logging`, `security` — read from current config and carried forward unless `apps.json` is edited directly. Exception: `security.ip_whitelist` / `security.ip_blacklist` come from the prompts (enforced by the global `IpFilter` middleware; both empty = allow all).

> Prior to v2.4.7 these four blocks were hardcoded and wiped on every run — upgrade if you use `synapps:reconfig` with customized themes/dependencies/CSP/logging.

**Run after:** `php artisan synapps:config` to recompile.

### synapps:vendor-fonts
Self-hosts a theme's Google Fonts (removes third-party font CDN calls).

```bash
php artisan synapps:vendor-fonts {theme?}
```

**What it does:**
1. Finds the `fonts.googleapis.com/css2` link in `theme.json`'s `headers.*`
2. Downloads the CSS + referenced `.woff2` files, rewrites CSS to reference them locally
3. Removes the Google `<link>`/`preconnect` entries and registers the local stylesheet in `theme.json`'s `css.*`
4. Atomic — nothing written to disk until every font file downloads successfully

**After running:** `npm run build` then `php artisan synapps:config`

### synapse:patch-composer
Merges `composer.local.json` into `composer.json` for local development with path repositories.

```bash
php artisan synapse:patch-composer
```

**What it does:**
1. Checks if `composer.local.json` exists in project root
2. Backs up current `composer.json` to `composer.original.json`
3. Deep merges `composer.local.json` into `composer.json`
4. Adds both files to `.gitignore` (idempotent)

**Use case:**
Perfect for local development when testing packages via path repositories without modifying the committed `composer.json`.

**Example `composer.local.json`:**
```json
{
    "repositories": [
        {
            "type": "path",
            "url": "./packages/synapse"
        }
    ],
    "require": {
        "vm-engine/synapse": "@dev"
    },
    "minimum-stability": "dev"
}
```

**After patching:**
```bash
composer update
```

**Idempotent:** Can re-run safely. Warns if already patched and asks for confirmation.

### synapse:restore-composer
Restores original `composer.json` from backup, undoing the patch.

```bash
php artisan synapse:restore-composer
```

**What it does:**
1. Checks if `composer.original.json` exists
2. Deletes current (patched) `composer.json`
3. Renames `composer.original.json` back to `composer.json`

**After restoring:**
```bash
composer update
```

**Safe:** Prompts for confirmation before restoring.

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
- `synapps/config/{apps,modules}.json` - Base configs
- `synapps/config/{apps,modules}.dev.json` - Dev overrides
- `config.json` - Protection rules

*Compiled:*
- `synapps/config/compiled/{apps,modules,binding}.json`

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
- **Registers SynapseFinder** — overrides Livewire's `livewire.finder` singleton to fix an MFC discovery bug where directories whose names contain "index" as a substring (e.g. `todo-index`) were incorrectly resolved (see `src/Finder/SynapseFinder.php`)

**2. SynapseLangServiceProvider**: Owns the translator; group files (`validation`, `auth`, `passwords`, `pagination`) resolve framework English → `lang/` here (ships `id`) → the project's `lang/`, key by key

**3. SynapseRouteServiceProvider**: Route registration
- `routes/web.php` - Standard (URL + name prefixed)
- `routes/web.backend.php` - Backend — registered by **synapps-auth** (not this provider) when installed: URL `{backend_prefix}/{module url_prefix}/*`, name `{backend_name_prefix}.{module id}.*`, `web`+`auth`+`2fa-refresh`+`backend-access` middleware. Both prefixes come from `config('synapps.auth.routes.*')` (defaults `/apps` and `backend`), set via `synauth:install` / `synauth:config` — never hardcode them; use `backend_route('module.route')` (`nameOnly: true` for `Route::has()`/`redirectRoute()`). In synapse stubs guard it with `function_exists('backend_route')`, since synapps-auth is optional.
- `routes/web.override.php` - Override (no URL prefix, name prefixed)
- `routes/api.php` - API routes (`/api/{prefix}/`, name `api.{id}.`)
- `routes/api.override.php` - API override routes (`/api/`, name `api.{id}.`, no module prefix)
- `routes/api.v{N}.php` - Versioned API routes (`/api/{version}/{prefix}/`, name `api.{version}.{id}.`) — auto-discovered via glob
- `routes/api.v{N}.override.php` - Versioned API override routes (`/api/{version}/`, name `api.{version}.{id}.`, no module prefix) — one per version

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

// Flush in-memory cache and re-load current language from DB (Octane workers, queue jobs)
DbLang::reload();

// Using helper function
$text = ___('welcome.title');
$text = ___('welcome.title', 'id');
```

**Service binding:** `'dblang'`
**Database table:** `languages` (language_code, key, value, label)
**Command:** `php artisan synapse:db-lang key value --label="Label" --language=en`

**Multi-language auto-creation:**
When `synapps.apps.multi_language` is `true`, setting a translation auto-creates empty entries for all `enabled_languages`.

### API Request Logging

Opt-in `api.log` middleware (`LogApiRequest`) that records every API request/response into an `api_request_logs` table (method, URL, status code, actor, IP, user agent, duration, redacted headers/body). Never applied globally — attach explicitly per route group:

```php
Route::middleware(['auth:sanctum', 'api.auth', 'api.token.expiry', 'throttle:api', 'api.log'])
    ->group(function () { /* audited routes */ });
```

**`SensitiveDataRedactor`** — recursively redacts keys from `config('synapps.apps.logging.sensitive_keys')` (case-insensitive) as `[REDACTED:{key}]`; `UploadedFile` values become `[FILE:{field}:{name}]`; oversized payloads (over `config('synapps.apps.logging.max_body_size')`) are truncated into a valid-JSON `{_truncated, original_size, preview}` envelope.

**Model:** `ApiRequestLog` (no `updated_at` — write-once). **Enum:** `ApiLogActorType` — `Guest` (unauthenticated) / `ApiUser` (`$user->is_api_user`) / `User` (otherwise).

Logging failures never break the actual API response — caught internally and logged via `Log::error()`.

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
├── theme.json              # Metadata and CDN resources
├── layouts/
│   ├── app.blade.php
│   ├── frontend.blade.php
│   └── guest.blade.php
├── css/
│   ├── theme-base.css      # Shared styles (every page)
│   ├── theme-app.css       # Backend-only styles
│   ├── theme-frontend.css  # Frontend-only styles
│   └── theme-guest.css     # Auth-only styles
├── js/
│   ├── theme-base.js       # Shared Alpine components (every page)
│   ├── theme-app.js        # Backend-only Alpine components
│   ├── theme-frontend.js   # Frontend-only JS
│   └── theme-guest.js      # Auth-only JS
└── assets/                 # Images, fonts, etc.
```

**Fallback Chain:**
1. Active theme layouts (`synapps/resources/themes/{active}/`)
2. Fallback theme layouts (`synapps/resources/themes/{fallback}/`)
3. Synapse default layouts (`synapps::components.layouts.*`)

**Blade Directives:**
- `@themeStyles('app')` - Render CDN links + Vite assets (`base.css` + `app.css`)
- `@themeScripts('app')` - Render CDN scripts + Vite assets (`base.js` + `app.js`)
- `@themeHeaders('app')` - Render CDN headers (meta tags from `theme.json`)

**Do NOT add `@vite()` calls to layout files** — `@themeStyles` / `@themeScripts` already include the Vite-compiled bundles.

**Helper Functions:**
- `theme_asset($path, $theme)` - Get theme asset URL
- `theme_path($path, $theme)` - Get theme filesystem path
- `active_theme()` - Get active theme name
- `theme_service()` - Get ThemeService instance

See [docs/THEMES.md](docs/THEMES.md) for complete theming guide.

### Excel Import/Export

Memory-efficient Excel import/export utility with streaming support, background processing, and resume capability.

**Libraries:** `openspout/openspout` ^4.0 (streaming), `phpoffice/phpspreadsheet` ^2.0 (templates)

```php
use VmEngine\Synapse\Services\Excel\ExcelImporter;
use VmEngine\Synapse\Services\Excel\ExcelExporter;

// Sync import
$rows = ExcelImporter::make('/path/to/file.xlsx')
    ->startingRow(2)
    ->mapColumns(['name', 'email', 'phone'])
    ->import();

// Chunked import
ExcelImporter::make('/path/to/file.xlsx')
    ->startingRow(2)
    ->mapColumns(['name', 'email'])
    ->chunk(1000, function (Collection $rows, int $chunkIndex) {
        User::insert($rows->toArray());
    });

// Background import
$task = ExcelImporter::make('/path/to/file.xlsx')
    ->startingRow(2)
    ->mapColumns(['name', 'email'])
    ->forUser(auth()->id())
    ->onQueue('imports')
    ->dispatch(UserImportProcessor::class, chunkSize: 1000);

// Sync export
ExcelExporter::make(User::query()->where('active', true))
    ->columns(['Full Name' => 'name', 'Email' => 'email'])
    ->sheet('Users Report')
    ->export('/path/to/output.xlsx');

// Styled export with column widths and formatting
ExcelExporter::make(User::query())
    ->columns([
        'Full Name' => 'name',
        'Email' => 'email',
        'Salary' => 'salary',
    ])
    ->columnStyles([
        'Full Name' => ['font' => ['bold' => true], 'width' => 30],
        'Email' => ['width' => 40],
        'Salary' => ['numberFormat' => ['formatCode' => '#,##0.00'], 'width' => 15],
    ])
    ->headerStyle([
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4472C4']],
    ])
    ->export('/path/to/output.xlsx');

// Template export
ExcelExporter::make(Invoice::query())
    ->columns(['invoice_number', 'client_name', 'total'])
    ->startingRow(5)
    ->template('/path/to/template.xlsx')
    ->export('/path/to/output.xlsx');

// Background export with styles
$task = ExcelExporter::make(ActiveUsersExport::class)
    ->columns(['Name' => 'name', 'Email' => 'email'])
    ->columnStyles(['Name' => ['width' => 30], 'Email' => ['width' => 50]])
    ->forUser(auth()->id())
    ->dispatch('/path/to/output.xlsx');

// Background export with user-defined filters
$task = ExcelExporter::make(YearlyReportExport::class)
    ->columns(['Title' => 'title', 'Amount' => 'amount'])
    ->withFilters(['year' => 2025, 'status' => 'approved'])
    ->forUser(auth()->id())
    ->dispatch('/path/to/report.xlsx');

// Resume failed tasks
ExcelImporter::resume($importTask);
ExcelExporter::resume($exportTask);

// Reference tracking (persist task lookup across page refreshes)
$task = ExcelExporter::make(InvoiceExport::class)
    ->reference('pm-invoice-export-42')
    ->dispatch('/path/to/output.xlsx');
$task = ExcelExport::findByReference('pm-invoice-export-42');

// Dot notation for relationships
ExcelExporter::make(Invoice::query()->with('client.company'))
    ->columns([
        'Invoice #' => 'invoice_number',
        'Client' => 'client.name',
        'Company' => 'client.company.name',
    ])
    ->export('/path/to/output.xlsx');

// Column transformers (serializable, works in background)
use VmEngine\Synapse\Services\Excel\Transformers\DateFormatTransformer;
use VmEngine\Synapse\Services\Excel\Transformers\BooleanTransformer;

ExcelExporter::make($query)
    ->columns([
        'Joined' => [
            'field' => 'created_at',
            'transformer' => DateFormatTransformer::class,
            'config' => ['format' => 'd/m/Y'],
        ],
        'Active' => [
            'field' => 'is_active',
            'transformer' => BooleanTransformer::class,
        ],
    ])
    ->export('/path/to/output.xlsx');
```

**Contracts:**
- `ProcessesImport` - Implement to process import chunks in background jobs
- `ExportQuery` - Implement to provide serializable query for background exports
- `ColumnTransformer` - Implement to create custom serializable value transformers

**Built-in Transformers:** `DateFormatTransformer`, `NumberFormatTransformer`, `BooleanTransformer`, `DefaultValueTransformer`, `UpperCaseTransformer`
**Models:** `ExcelImport`, `ExcelExport` - Task tracking with status, progress, heartbeat, reference
**Enum:** `ExcelTaskStatus` - Pending, Processing, Completed, Failed, Cancelled
**Events:** `ExcelImportCompleted`, `ExcelImportFailed`, `ExcelExportCompleted`, `ExcelExportFailed`
**Command:** `php artisan synapse:excel-cleanup --timeout=3900`

See [docs/EXCEL.md](docs/EXCEL.md) for complete API documentation.

### Breadcrumbs
```php
$breadcrumbs = Breadcrumbs::make('Home', '/')
    ->add('Users', '/users')
    ->add('John Doe', '/users/1', 'fa-user');
```

**Methods:** `make()`, `add()`, `hasItems()`, `items()`, `count()`, `current()`

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
- Uses Livewire v4's `addNamespace()` for automatic component discovery
- Auto-discovers MFC/SFC components from `resources/views/components/` (including nested)
- Auto-discovers class-based Livewire components from `src/Livewire/`
- Auto-discovers class-based Blade components from `src/View/Components/`
- Automatic prefix detection from namespace
- Supports nested components with kebab-case naming

**Example Registration (namespace notation):**
- `src/Livewire/ExampleForm.php` → `<livewire:example::example-form />`
- `resources/views/components/todo-form/todo-form.php` → `<livewire:example::todo-form />`
- `resources/views/components/forms/contact-form/contact-form.php` → `<livewire:example::forms.contact-form />`
- `src/View/Components/Alert.php` → `<x-example::alert />`

**Methods:**
- `registerComponents()` - Register all components (Livewire + Blade)
- `registerLivewireNamespace()` - Register Livewire namespace (MFC/SFC + class-based)
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

### WithReturnUrl

Safe return-URL trait for Livewire form components. Binds `?back=` query parameter and provides a validated redirect method.

```php
use VmEngine\Synapse\Traits\WithReturnUrl;

class PostForm extends Component
{
    use WithReturnUrl;
    // $backUrl auto-bound from ?back= via #[Url]

    public function save(): void
    {
        // redirects to $backUrl if same-origin, else falls back to route
        $this->redirectBack('posts.index');
    }
}
```

**Methods:**
- `redirectBack(string $defaultRoute, array $parameters = [])` - Redirect to back URL or named route

**JavaScript helper:** `withBack(url)` — global JS function (injected by `synapps:patch`) that appends `?back=<current-url>` to a URL for use in Blade links.

See [docs/TRAITS.md#withreturnurl](docs/TRAITS.md#withreturnurl) for complete documentation.

---

### RemembersQueryParams

Persists Livewire component properties (search, filters, pagination) to session so list pages restore state on back-navigation. URL query params always take precedence over session.

```php
use VmEngine\Synapse\Traits\RemembersQueryParams;

class PostList extends Component
{
    use RemembersQueryParams;

    public string $search = '';
    public string $status = 'all';

    protected function rememberedParams(): array
    {
        return ['search', 'status'];
    }
}
```

**Methods:**
- `rememberedParams()` - Declare which properties to persist (override in component)
- `rememberKey()` - Override session key (default: `synapse:query_params:{ClassName}`)
- `forgetRememberedParams()` - Clear session storage (call from "reset filters")
- `getRememberedParamValues()` - Read stored values without modifying properties

See [docs/TRAITS.md#remembersqueryparams](docs/TRAITS.md#remembersqueryparams) for complete documentation.

---

## Blade Components

> Registered tag is `synapse-{name}` (single hyphen, e.g. `<x-synapse-alert>`), not the `x-synapse::{name}` namespace notation — components are registered individually via `$blade->component(...)` in `SynapseViewServiceProvider`, not via a Blade component namespace.

**Form Components:**
- `<x-synapse-checkboxes name="" :options="[]" :model="[]" />`
- `<x-synapse-radio-buttons name="" :options="[]" :model="" />`
- `<x-synapse-password />`
- `<x-synapse-currency currency="$" :decimals="0" :raw="false" />` — `raw` sends the plain number ("1250000.50") to `wire:model` instead of the formatted text; `<x-synapse-field type="currency" prefix="Rp" :decimals="2">` renders it with label/error wiring.
- `<x-synapse-datepicker :flatpickr="{}" />`
- `<x-synapse-search-box placeholder="" />` — left search icon, optional `trailing` slot, all other attributes (`wire:model`, ...) pass through to the `<input>`.
- `<x-synapse-adv-select wire-model="" :options="[['value'=>..,'label'=>..]]" />` — replaces the removed `select`/`multi-select`/`select2`; supports `multiple`, remote `searchMethod`/`loadMethod`, `disabled`; an option's `depth` indents it in the dropdown (tree pickers, no label prefixes).
- `<x-synapse-toggler label="" />`
- `<x-synapse-password-generator />`, `<x-synapse-drag-n-drop />`, `<x-synapse-file-drop />`

**List Helpers:**
- `<x-synapse-per-page-selector :paginator="$items" />` — 10/15/25/50 bound to the host's `$perPage` + pagination links.
- `<x-synapse-sort-icon field="" :sort-field="$sortField" :sort-direction="$sortDirection" />` — calls the host's `sortBy()`.
- `<x-synapse-excel-export-button mode="sync|async" action="" ... />` — pair async mode with the `WithExcelExport` trait (docs/EXCEL.md).

**Layout Components:**
- `<x-synapse-panel title="">` — card-shell wrapper with a `toolbar` slot; `collapsible`/`collapsed` for a click-to-toggle body.
- `<x-synapse-stat-tile icon="" color="" label="" value="" />` — single-metric card. `variant="tile"` (default, icon square + label/value) or `variant="card"` (title-row layout, `iconPosition`, `tinted`, `sublabel`, `href`/`clickable`). `label`/`value`/(`sublabel`) accept matching named slots for rich content.

**Planning & Layout Components (3.1):**
- `<x-synapse-color-picker wire:model="" :swatches="['#rrggbb']" />` — native picker + hex field + swatches; validate with `hex_color`.
- `<x-synapse-steps :steps="[]" :current="1" go-method="" />` — server-driven wizard indicator, no JS.
- `<x-synapse-split-pane aside-width="20rem" resizable storage-key="">` + `<x-slot:aside>` — stacks below `lg`.
- `<x-synapse-kanban sort-method="">` → `.column id="" label="" color=""` → `.card id=""` — native `wire:sort` groups; column reads `sortMethod`/`group` via `@aware`.
- `<x-synapse-calendar month="Y-m" :events="[]" nav-method="" event-method="" day-method="" />` — month grid built in PHP, mobile agenda.
- Values interpolated into `wire:*` expressions go through `Components\Concerns\SanitizesWireArguments` (`wireMethod()`, `safeKey()`).

**UI Components:**
- `<x-synapse-copy-button text="" label="Copy" :icon-only="true" />` — clipboard copy with feedback + execCommand fallback.
- `<x-synapse-confirm-dialog title="" message="" confirmText="" />`
- `<x-synapse-drawer title="" name="">` + optional `<x-slot:footer>`: `open-drawer-{name}` / `close-drawer-{name}` window events (no `name` = parent scope `drawerOpen`); attributes go to the panel; `title` is raw HTML, so never put user input in it.
- `<x-synapse-modal name="" max-width="xl" title="">` + optional `<x-slot:footer>` — `open-modal-{name}` / `close-modal-{name}` window events, Escape closes; keep `name` lowercase. `title` renders the header + close button.
- `<x-synapse-field name="" label="" type="text" hint="" prefix="" error="" required />` — form-box + label + input + error/hint (aria-linked); slot replaces the input; `error` = validation key when it differs from `name`.
- `<x-synapse-empty-state icon="" title="" text="">` — empty list/panel block; slot = action.
- `<x-synapse-confirm-button method="" token="" label="" title="" message="" />` — opens the confirm dialog; `token` (delete_token) for deletes, `param` otherwise; `labelled` for a text button.
- `<x-synapse-save-button action="save" />` — busy state; `<x-synapse-form-action-bar action="" cancel-url="" />` — sticky bar for long page forms; `<x-synapse-section-heading title="" help="" />`.
- `<x-synapse-modal-header modal="" title="" />` / `<x-synapse-modal-footer modal="" />` — chrome for a child form inside `<x-synapse-modal labelledby="modal-{name}-title">`.
- `<x-synapse-breadcrumbs :breadcrumbs="$this->breadcrumbs" />` and `<x-synapse-loader />` (once per layout) — replace the deprecated `layouts.partials.{breadcrumbs,loader}` includes.
- `<x-synapse-lightbox name="" />` — `$dispatch('open-lightbox-{name}', { url })`.
- `<x-synapse-toastr />`
- `<x-synapse-alert type="success|warning|danger|info" message="" title="" :dismissible="true" :border="false" :solid="false" />`
- `<x-synapse-badge color="gray|brand|success|warning|danger|info" size="sm|md" icon="">` — pill status badge.

All component `Alpine.data()` registrations live in `stubs/synapps/resources/js/synapse-components.js` (copied to `synapps/resources/js/`, imported by `synapps.js`, installed into existing projects by `synapps:patch`) — never add inline `<script>` blocks to component views: they don't run when a component first appears through a Livewire morph, and inline object literals break the CSP-safe Alpine build.

**Alert Component (Detailed):**

Auto-assigns icons by type:
- Success: `ph ph-check-circle`
- Warning: `ph ph-warning`
- Danger/Error: `ph ph-warning-circle`
- Info: `ph ph-info`

(Pass `icon="..."` to override — any icon font class works.)

Features: Alpine.js animations, dark mode, slot content, ARIA attributes

```blade
<x-synapse-alert type="success" message="Saved!" />

<x-synapse-alert type="danger" title="Errors" :dismissible="false">
    <ul class="list-disc list-inside">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</x-synapse-alert>
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

**Framework:** Pest 5.x (PHPUnit 13) with Orchestra Testbench 11

**Structure:**
```
tests/
├── Feature/
│   ├── Commands/SynAppsCommandsTest.php
│   ├── Excel/
│   │   ├── ExcelImportTest.php
│   │   ├── ExcelExportTest.php
│   │   └── ExcelJobTest.php
│   └── ModuleDiscoveryTest.php
├── Unit/
│   ├── Components/BladeComponentsTest.php
│   ├── Services/
│   │   ├── Excel/
│   │   │   ├── ExcelImporterTest.php
│   │   │   ├── ExcelExporterTest.php
│   │   │   ├── ColumnMapperTest.php
│   │   │   └── ChunkIteratorTest.php
│   │   ├── Helper/BreadcrumbsTest.php
│   │   ├── ConfigGenerator/
│   │   ├── ConfigGeneratorsTest.php
│   │   └── SynapseUtilsTest.php
│   ├── HelperFunctionsTest.php
│   └── ServiceProvidersTest.php
├── fixtures/
│   ├── sample.csv
│   ├── sample.xlsx
│   └── template.xlsx
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

**Coverage (300+ tests):**
- Helper functions: 195+ tests
- Blade components: 70+ tests
- Excel import/export: 68 tests
- Config generators: 51 tests
- Services: 39 tests
- Module discovery: 23 tests
- Commands: 9 tests

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
- Laravel Framework ^13.0 (PHP ^8.5)
- Livewire ^4.0
- composer/semver ^3.0
- openspout/openspout ^4.0
- phpoffice/phpspreadsheet ^2.0

**Dev:**
- pestphp/pest ^5.0
- pestphp/pest-plugin-laravel ^5.0
- orchestra/testbench ^11.0
- laradumps/laradumps ^4.0
- phpstan/phpstan ^2.1
- larastan/larastan ^3.8
- laravel/pint ^1.26
