# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**vm-engine/synapse** is a Laravel package that provides a sophisticated modular architecture system for Laravel applications. It enables both directory-based modules (in `synapps/modules/`) and package-based modules (with `synmod-`, `synmodule-`, or `synapps-` prefix in vendor).

## Package Structure

This is a **library** package, not a standalone Laravel application. It provides:
- Service Providers for automatic module discovery and registration
- Configuration generators that scan and compile module configurations
- Console commands for module management
- Livewire components and helpers
- Route management for modular applications

## Artisan Commands

### Module Discovery
```bash
php artisan synapps:discover
```
**Critical command** - Discovers modules in `synapps/modules/` and scans their dependencies:
- Discovers directory-based modules and reads `module.json` files
- Auto-generates PSR-4 namespaces and adds them to `composer.json`
- Discovers npm/composer dependencies from modules and updates package files
- Runs `composer dump-autoload` or `composer update` as needed
- Runs `npm install` if new dependencies found
- Compiles assets with `npm run build` if needed

### Resource Patching
```bash
php artisan synapps:patch-resources
```
Patches `resources/js/app.js` and `resources/css/app.css` to import Synapse resource files automatically.

## Architecture

### Configuration System

The package uses a **dual-configuration generator** pattern:

#### 1. SynapseConfigGenerator
Generates the `synapse.php` config by scanning filesystem for modules:
- Scans `synapps/modules/*` for directory-based modules
- Scans `vendor/*/*` with prefixes `synmod-`, `synmodule-`, or `synapps-` for package modules
- Builds paths for: migrations, seeders, views, language files, namespaces
- Relies on compiled JSON files in `synapps/config/compiled/` (apps.json, system.json)
- Returns default empty config if compiled files don't exist

#### 2. SynAppsConfigGenerator
Generates the `synapps.php` config using the Builder pattern:
- Uses ConfigGenerator\Builder class with multiple sub-builders:
  - **Apps**: App-level configuration
  - **System**: System-level configuration
  - **Modules**: Module discovery and configuration merging
  - **Binding**: Class/interface bindings
- Reads `config.json` for allowed/protected keys
- Processes module configurations in order: init → preLoop → loop → postLoop → finalize
- Writes compiled configs to `synapps/config/compiled/*.json`

### Module Discovery Flow

1. **Builder\Modules** scans for `module.json` files:
   - `synapps/modules/*/module.json` (directory modules)
   - `vendor/*/synmod-*/module.json` (package modules)
   - `vendor/*/synmodule-*/module.json`
   - `vendor/*/synapps-*/module.json`

2. Determines if module is package-based:
   - `is_package: 1` if in vendor directory
   - `is_package: 0` if in synapps/modules

3. Sorts modules by `load_priority` (default: 99)

4. Merges with override configs:
   - `synapps/config/modules.json` (production overrides)
   - `synapps/config/modules.dev.json` (dev overrides)
   - Protected keys (id, namespaces, git, load_priority, is_package) cannot be overridden

5. Writes final compiled config to `synapps/config/compiled/modules.json`

### Service Provider Registration

The package registers **4 service providers** automatically via Laravel package discovery:

1. **SynapseServiceProvider** (main):
   - Publishes config files (`synapse.php`, `synapps.php`)
   - Publishes assets to public directory
   - Registers artisan commands
   - **Auto-registers module ServiceProviders** for directory-based modules only
   - Pattern: `SynApps\Modules\{ModuleName}\{ModuleName}ServiceProvider`
   - Binds aliases, classes, and interfaces from config
   - Loads migrations and seeders from all modules
   - Registers language files from modules

2. **SynapseLangServiceProvider**: Language file management

3. **SynapseRouteServiceProvider**:
   - Automatically registers routes from `routes/web.php` and `routes/api.php` for each module
   - Applies module-specific URL prefixes from `url_prefix` in module.json
   - Routes named with module ID prefix: `{module}.{route}`

4. **SynapseViewServiceProvider**: View and component registration

### Protected vs Allowed Configuration Keys

Defined in `config.json`:

**Protected Module Keys** (cannot be overridden in modules.json):
- `id`, `namespaces`, `git`, `load_priority`, `is_package`

**Allowed Dev Keys** (can be set in apps.dev.json):
- `mode`, `laravel_key`, `locale`, `fallback_locale`, `public_path`, `public_url`, `userfile_url`, `binding`

### Namespace Pattern

Package namespace: `VmEngine\Synapse\`

Key classes:
- `Services\SynapseConfigGenerator` - Filesystem-based config generation
- `Services\SynAppsConfigGenerator` - Builder-based config generation
- `Services\ConfigGenerator\Builder` - Main builder coordinator
- `Services\ConfigGenerator\BuilderBase` - Base class for all builders
- `Services\SynapseUtils` - Utility methods for module operations
- `Console\Commands\SynAppsDiscover` - Module discovery command
- `Console\Commands\SynAppsPatchResources` - Resource patching command

### Helper Functions

The package loads `src/helper.php` which likely provides helper functions like:
- `synapps_path()` - Get path to synapps directory
- Other module-related helpers

## Development Notes

### When Modifying Config Generators

1. **SynapseConfigGenerator** depends on compiled JSON files existing
2. **Builder pattern** in SynAppsConfigGenerator executes in specific order:
   - `init()` - Initialize builder state
   - `preLoop()` - Before module iteration
   - `loop($moduleName, $moduleConfig)` - Per-module processing
   - `postLoop()` - After module iteration
   - `finalize()` - Write compiled configs

3. Always run `php artisan synapps:discover` after:
   - Adding new modules
   - Changing module.json files
   - Modifying module dependencies

### Module Configuration Schema

Each module requires a `module.json`:
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
    "description": "Description",
    "version": "0.1.0",
    "author": "Author Name",
    "email": "author@example.com",
    "url_prefix": "url-prefix",
    "load_priority": 99,
    "dependencies": {
        "npm": { "main": {}, "dev": {} },
        "composer": { "main": {}, "dev": {} }
    }
}
```

### Package-based vs Directory-based Modules

**Directory-based** (`synapps/modules/`):
- Requires manual PSR-4 registration in Laravel app's composer.json (or use `synapps:discover`)
- Requires ServiceProvider in `src/{ModuleName}ServiceProvider.php`
- ServiceProvider auto-registered by SynapseServiceProvider

**Package-based** (`vendor/*/synmod-*`):
- Must be Composer packages with `type: "synapse-module"` (optional, not enforced)
- Must have `synmod-`, `synmodule-`, or `synapps-` prefix
- ServiceProvider registered via Laravel package discovery (in package's composer.json)
- No manual namespace registration needed

## Dependencies

- Laravel Framework 12.x
- Livewire 3.x
- composer/semver ^3.0 (for dependency version comparison)
