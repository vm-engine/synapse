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
- Configuration generators with Builder pattern
- Livewire components and helpers
- Automatic route/provider registration

## Artisan Commands Quick Reference

| Command | Purpose | When to Run | Idempotent | Auto-run by Setup |
|---------|---------|-------------|------------|-------------------|
| `synapps:setup` | Initial setup wizard | Once on fresh install | No (overwrites) | N/A |
| `synapps:discover` | Module discovery & deps | After module changes | Yes | ✅ Yes |
| `synapps:patch-resources` | Patch JS/CSS imports | After install, if assets missing | Yes | ✅ Yes |
| `synapps:patch-tests` | Configure test discovery | After install, new modules | Yes | ✅ Yes |
| `synapps:link-errors` | Link error views | After custom error pages | Yes | ✅ Yes |

### synapps:setup
Interactive wizard that:
1. Collects project info (app code, name, author, URLs, prefixes)
2. Copies stub directory from `src/stubs/synapps/` to `synapps/`
3. Generates `synapps/config/apps.json`
4. **Automatically runs these setup commands in sequence:**
   - `vendor:publish --tag=synapps-config --force` - Publish config files (`config/synapse.php`, `config/synapps.php`)
   - `synapps:link-errors` - Create symlink for error views
   - `synapps:patch-resources` - Patch JS/CSS imports
   - `synapps:patch-tests` - Configure testing infrastructure
   - `synapps:discover` - Discover modules and update dependencies

**Follow-up:** Run `config:cache` before migrating

> **Note:** The setup command now handles all initialization automatically, including publishing configuration files. You only need to manually run the individual commands if troubleshooting or have specific requirements.

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
Patches Laravel resource files:
- **resources/js/app.js** - Adds import for `synapps/resources/js/synapps.js`
- **resources/css/app.css** - Adds import for `synapps/resources/css/synapps.css`

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

**Dual-generator pattern:**

**1. SynapseConfigGenerator** - Generates `synapse.php`:
- Scans filesystem for modules
- Builds paths for migrations, seeders, views, languages
- Relies on compiled JSON in `synapps/config/compiled/`

**2. SynAppsConfigGenerator** - Generates `synapps.php` using Builder pattern:
- **Apps Builder**: App-level config (`apps.json` + `apps.dev.json`)
- **System Builder**: System config with protected keys (`system.json` + `system.dev.json`)
- **Modules Builder**: Module discovery and merging (`modules.json` + `modules.dev.json`)
- **Binding Builder**: Service container bindings

**Builder Lifecycle (5 phases):**
1. `init()` - Initialize state, load configs
2. `preLoop()` - Execute before module iteration
3. `loop($name, $config)` - Process each module
4. `postLoop()` - Execute after all modules
5. `finalize()` - Write compiled configs to disk

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
- Binds aliases/classes/interfaces
- Loads migrations, seeders, languages

**2. SynapseLangServiceProvider**: Language files

**3. SynapseRouteServiceProvider**: Route registration
- `routes/web.php` - Standard (URL + name prefixed)
- `routes/web.backend.php` - Backend (auto-auth, `/admin/*`, `backend.` prefix)
- `routes/web.override.php` - Override (no URL prefix, name prefixed)
- `routes/api.php` - API routes

**4. SynapseViewServiceProvider**: Views and components

---

## Helper Services

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
php packages/synapse/generate-ide-helper.php

# With additional paths
php packages/synapse/generate-ide-helper.php \
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
```bash
composer test                           # All tests
composer test-coverage                  # With coverage
./vendor/bin/pest tests/Unit            # Specific suite
./vendor/bin/pest --filter="breadcrumbs" # Filter
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
