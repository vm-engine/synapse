# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## ⚠️ IMPORTANT: Git Commit and Push Policy

**NEVER commit and push changes without explicit user approval.**

When you complete tasks:
1. Show the user what changes were made
2. Wait for the user to review the changes
3. Only commit and push after the user explicitly approves

Do NOT automatically commit and push, even if changes are complete. Always ask first:
- "Would you like me to commit and push these changes?"
- "Ready to commit? Here's what will be included..."

This applies to ALL repositories in this project (main repo, packages, synapps, etc.).

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

### Initial Setup: synapps:setup
**Class:** `VmEngine\Synapse\Console\Commands\SynAppsSetup`
**Signature:** `php artisan synapps:setup`

Interactive setup wizard for initializing a new SynApps installation.

#### What it Does

1. **Collects Project Information via Prompts**
   - **App code** (required, snake-case) - Unique application identifier
   - **App name** (required) - Human-readable application name
   - **Author** (required) - Developer/organization name
   - **Author Email** (required) - Contact email
   - **GIT Repository** (optional) - Repository URL
   - **Dev Domain URL** (optional, with protocol) - Development environment URL
   - **Prod Domain URL** (optional, with protocol) - Production environment URL
   - **Frontend Prefix** (default: '/') - URL prefix for frontend routes
   - **API Prefix** (default: '/api') - URL prefix for API routes
   - **Application Title** (default: app name) - Display title
   - **Application Description** (optional, multi-line) - Project description

2. **Copies Stub Directory Structure**
   - Copies from package: `src/stubs/synapps/`
   - Copies to project: `synapps/`
   - Creates complete folder structure:
     - `synapps/config/` - Configuration files
     - `synapps/modules/` - Directory for modules
     - `synapps/resources/` - Shared resources
     - Additional stub directories as defined

3. **Generates synapps/config/apps.json**
   - Creates initial configuration file with collected data:
     ```json
     {
         "app_code": "my_app",
         "app_name": "My Application",
         "author": "John Doe",
         "email": "john@example.com",
         "git_repo": "https://github.com/user/repo",
         "base_version": "",
         "urls": {
             "domain": {
                 "dev": "http://localhost:8000",
                 "prod": "https://example.com"
             },
             "prefix": "/",
             "api_prefix": "/api"
         },
         "title": "My Application",
         "description": "Application description",
         "dependencies": {
             "npm": { "main": {}, "dev": {} },
             "composer": { "main": {}, "dev": {} }
         }
     }
     ```

4. **Displays Next Steps**
   - Run `php artisan synapps:discover` - Discover modules and dependencies
   - Run `php artisan config:cache` - Cache configuration
   - Run `php artisan synapps:patch-resources` - Patch resource files
   - Initialize Git repository in synapps folder

#### When to Run

Run this command:
- **Once** when setting up a new Laravel project with Synapse
- After fresh installation of the Synapse package
- To reinitialize synapps directory structure (warning: overwrites existing)

#### Output Example
```
┌ App code ────────────────────────────────────┐
│ my_app                                       │
└──────────────────────────────────────────────┘

┌ App name ────────────────────────────────────┐
│ My Application                               │
└──────────────────────────────────────────────┘

... (additional prompts)

✓ Setup complete.

ℹ Run `synapps:discover` and `config:cache` artisan command before migrating.
ℹ Run `synapps:patch-resources` before compiling assets
ℹ Don't forget to init Git Repository in synapps folder
```

#### Important Notes
- This command uses Laravel Prompts for interactive input
- All prompts use validation to ensure required fields are filled
- The command overwrites existing `synapps/` directory if it exists
- Empty dependency objects use `stdClass` to ensure JSON encoding as `{}`
- Setup should be followed by `synapps:discover` to complete installation

### Module Discovery: synapps:discover
**Class:** `VmEngine\Synapse\Console\Commands\SynAppsDiscover`
**Signature:** `php artisan synapps:discover`

**Critical command** - Comprehensive module discovery and dependency management.

#### What it Does

1. **Discovers Directory-based Modules**
   - Scans `synapps/modules/*/module.json`
   - Extracts module metadata and namespace requirements
   - Reports: "Discovered Module: {name} at {path}"

2. **Generates PSR-4 Autoload Entries**
   - For each module, adds 3 namespace entries to `composer.json`:
     - `SynApps\Modules\{ModuleName}\` → `synapps/modules/{module-name}/src/`
     - `SynDB\Modules\{ModuleName}\Seeders\` → `synapps/modules/{module-name}/database/seeders/`
     - `SynDB\Modules\{ModuleName}\Factories\` → `synapps/modules/{module-name}/database/factories/`
   - Writes updated `composer.json`

3. **Discovers Package-based Module Dependencies**
   - Scans vendor directory for package modules:
     - `vendor/*/synmod-*/module.json`
     - `vendor/*/synmodule-*/module.json`
     - `vendor/*/synapps-*/module.json`
   - Extracts dependencies from package modules

4. **Aggregates Dependencies from All Sources**
   - Directory-based modules (`module.json`)
   - Package-based modules (`module.json`)
   - SynApps config (`synapps/config/apps.json`)
   - SynApps dev config (`synapps/config/apps.dev.json`)

5. **Smart Dependency Merging**
   - Uses `composer/semver` to compare versions
   - Only adds dependency if:
     - Not already installed, OR
     - Required version is greater than installed version
   - Tracks separately:
     - NPM dependencies
     - NPM devDependencies
     - Composer require
     - Composer require-dev

6. **Updates Package Managers**
   - **Composer:**
     - Writes updated `composer.json`
     - Runs `composer update` if new dependencies found
     - Runs `composer dump-autoload` if only namespaces changed
   - **NPM:**
     - Writes updated `package.json`
     - Runs `npm install` if new dependencies found
     - Runs `npm run build` if dependencies were installed

7. **Final Steps**
   - Displays summary: namespaces added, dependencies discovered
   - Reminds: "Please run `php artisan config:cache`"
   - Auto-compiles assets if npm dependencies changed

#### When to Run

Run this command:
- After creating a new directory-based module
- After modifying `module.json` in any module
- After adding dependencies to module configurations
- When module discovery seems stale or incorrect
- After composer install/update of package modules

#### Output Example
```
Discovered Module: Example Module at synapps/modules/example
Discovered Dependency: alpinejs - ^3.0
Added 3 Namespaces
Discovered 1 NPM Dependencies
Updating composer.json
Running composer dump-autoload
Updating package.json
Running npm install
Compiling assets
Please run `php artisan config:cache` after this command.
```

### Resource Patching: synapps:patch-resources
**Class:** `VmEngine\Synapse\Console\Commands\SynAppsPatchResources`
**Signature:** `php artisan synapps:patch-resources`

Automatically patches Laravel's main resource files to import Synapse resources.

#### What it Does

1. **Patches resources/js/app.js**
   - Searches for existing Synapse import line
   - If not found:
     - Locates last `import` statement
     - Inserts after last import:
       ```js
       import '/absolute/path/to/synapps/resources/js/synapps.js';
       ```

2. **Patches resources/css/app.css**
   - Searches for existing Synapse import line
   - If not found:
     - Locates last `@import` statement
     - Inserts after last import:
       ```css
       @import '/absolute/path/to/synapps/resources/css/synapps.css';
       ```

3. **Idempotent Operation**
   - Safe to run multiple times
   - Only patches if import doesn't already exist
   - Preserves existing file structure

#### When to Run

Run this command:
- After fresh installation of Synapse package
- If Synapse styles/scripts not loading
- After resetting resource files to defaults

#### Output
```
Resource files patched.
```

#### Notes
- Uses absolute paths to ensure imports work regardless of build config
- Inserts imports strategically after existing imports to maintain load order
- Does not modify or remove existing imports

### Test Configuration: synapps:patch-tests
**Class:** `VmEngine\Synapse\Console\Commands\SynAppsPatchTests`
**Signature:** `php artisan synapps:patch-tests`

Comprehensive test configuration command that sets up everything needed for proper testing with in-memory database and module discovery. This command patches three key files: `tests/Pest.php`, `.env.testing`, and `composer.json`.

#### What it Does

**1. Patches `tests/Pest.php`** (Module Test Discovery)
   - Checks if `tests/Pest.php` exists
   - Locates the `pest()->extend()->in()` chain (with or without `->use()`)
   - Adds paths for directory-based modules: `../synapps/modules/*/tests/Feature` and `../synapps/modules/*/tests/Browser`
   - Adds paths for package modules: `../vendor/*/synmod-*/tests/`, `../vendor/*/synmodule-*/tests/`, `../vendor/*/synapps-*/tests/`
   - Adds `'Browser'` directory for Pest Browser Testing
   - Removes duplicates automatically
   - Preserves all existing paths and configuration

**2. Creates/Updates `.env.testing`** (In-Memory Database Configuration)
   - Creates `.env.testing` file if it doesn't exist
   - Configures in-memory SQLite database: `DB_DATABASE=:memory:`
   - Sets test-specific configurations: `CACHE_STORE=array`, `QUEUE_CONNECTION=sync`, etc.
   - Prevents tests from touching actual database
   - If file exists, checks if database configuration is correct

**3. Updates `composer.json` Test Script** (Proper Test Workflow)
   - Ensures test script includes: `config:clear`, `config:cache`, and `test --env=testing`
   - Adds `--env=testing` flag to ensure `.env.testing` is loaded
   - Maintains other composer scripts unchanged
   - Only updates if test script exists and needs modification

#### Module Test Paths Added

```php
pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in(
        'Feature',
        'Browser',
        '../synapps/modules/*/tests/Feature',
        '../vendor/*/synmod-*/tests/Feature',
        '../vendor/*/synmodule-*/tests/Feature',
        '../vendor/*/synapps-*/tests/Feature',
        '../synapps/modules/*/tests/Browser',
        '../vendor/*/synmod-*/tests/Browser',
        '../vendor/*/synmodule-*/tests/Browser',
        '../vendor/*/synapps-*/tests/Browser'
    );
```

#### When to Run

Run this command:
- After fresh installation of Synapse package
- After creating your first module with tests
- If module tests are not being discovered by Pest
- After resetting `tests/Pest.php` to defaults

#### Output Examples

**First-time setup (all files created/updated):**
```
✓ tests/Pest.php patched successfully!
✓ .env.testing created/updated successfully!
✓ composer.json test script updated successfully!

✓ Test configuration completed successfully!
ℹ ✓ tests/Pest.php - Module paths added
ℹ ✓ .env.testing - In-memory database configured
ℹ ✓ composer.json - Test script updated
```

**Already configured:**
```
ℹ tests/Pest.php is already patched with module paths.
ℹ .env.testing already configured correctly.
ℹ composer.json test script already configured correctly.

✓ Test configuration completed successfully!
ℹ ✓ tests/Pest.php - Module paths added
ℹ ✓ .env.testing - In-memory database configured
ℹ ✓ composer.json - Test script updated
```

**Partial configuration (some files need updating):**
```
ℹ tests/Pest.php is already patched with module paths.
✓ .env.testing created/updated successfully!
ℹ composer.json test script already configured correctly.

✓ Test configuration completed successfully!
ℹ ✓ tests/Pest.php - Module paths added
ℹ ✓ .env.testing - In-memory database configured
ℹ ✓ composer.json - Test script updated
```

#### Usage

```bash
# Patch tests/Pest.php to include module paths
php artisan synapps:patch-tests

# Safe to run multiple times (idempotent)
php artisan synapps:patch-tests
php artisan synapps:patch-tests  # Will detect already patched
```

#### How It Works

The command uses regex pattern matching to:
1. Find the `pest()->extend()->use()->in(...)` chain
2. Parse existing paths from the `->in()` method
3. Merge module paths with existing paths (avoiding duplicates)
4. Rebuild the configuration with proper formatting
5. Write back to `tests/Pest.php`

#### Notes
- **Idempotent:** Safe to run multiple times - detects what's already configured and skips unnecessary changes
- **Preserves existing paths:** All original test paths in `tests/Pest.php` are kept
- **Non-destructive:** Only adds/updates necessary configuration, never removes existing settings
- **Proper formatting:** Maintains consistent indentation and structure in all files
- **Critical for testing:** Ensures tests use in-memory database and never touch actual database
- Module tests must follow the standard structure: `{module}/tests/Feature/` and `{module}/tests/Browser/`

#### Files Created/Modified

1. **tests/Pest.php** - Adds module test paths
2. **.env.testing** - Creates with in-memory database configuration
3. **composer.json** - Updates test script with proper flags

### Error Pages Linking: synapps:link-errors
**Class:** `VmEngine\Synapse\Console\Commands\SynAppsLinkErrors`
**Signature:** `php artisan synapps:link-errors`

Creates a link from Laravel's `resources/views/errors` to `synapps/resources/views/errors` so Laravel automatically uses custom error pages from the synapps directory.

#### What it Does

1. **Validates Source Directory**
   - Checks if `synapps/resources/views/errors` exists
   - Displays error if source directory is missing

2. **Removes Existing Link/Directory**
   - Automatically removes any existing link, junction, or directory at `resources/views/errors`
   - Handles symlinks, Windows junctions, and regular directories
   - Clears PHP file stat cache to ensure accurate detection

3. **Creates New Link**
   - **Tries symlink first** - Works on Linux/Mac, or Windows with administrator privileges
   - **Falls back to Windows junction** - On Windows without admin rights (most common scenario)
   - Junctions don't require administrator privileges on Windows

4. **Cross-Platform Support**
   - Unix/Linux: Creates standard symlink
   - macOS: Creates standard symlink
   - Windows (with admin): Creates symlink
   - Windows (without admin): Creates junction using `mklink /J`

#### When to Run

Run this command:
- After creating custom error pages in `synapps/resources/views/errors`
- After modifying error page templates in synapps
- To refresh the link if it becomes broken or outdated
- During initial project setup to integrate custom error handling

#### Output Examples

**Successful link creation:**
```
ℹ Existing link/directory found. Removing...
ℹ Symlink failed, trying Windows junction...
✓ Link created successfully!
ℹ resources/views/errors → synapps/resources/views/errors
```

**Source directory missing:**
```
✗ Source directory does not exist: /path/to/synapps/resources/views/errors
ℹ Please ensure synapps/resources/views/errors directory exists first.
```

#### Usage

```bash
# Create/refresh error pages link
php artisan synapps:link-errors

# Safe to run multiple times (idempotent)
php artisan synapps:link-errors
php artisan synapps:link-errors  # Will remove and recreate
```

#### How Laravel Uses Error Views

Once linked, Laravel automatically uses error pages from `synapps/resources/views/errors/` when:
- HTTP 404 (Not Found) occurs
- HTTP 403 (Forbidden) occurs
- HTTP 500 (Internal Server Error) occurs
- HTTP 503 (Service Unavailable) occurs
- HTTP 419 (Page Expired / CSRF Token Mismatch) occurs
- Any other HTTP error with a corresponding `{code}.blade.php` file

#### Notes
- **Idempotent:** Safe to run multiple times - automatically removes and recreates link
- **No admin required on Windows:** Uses junctions which don't need elevated privileges
- **Preserves original files:** The command creates a link, not a copy - changes in synapps are immediately reflected
- **Automatic fallback:** Tries the best method for your OS automatically
- Custom error pages should be created in `synapps/resources/views/errors/` before running this command

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
   - Automatically registers routes from multiple route files for each module:
     - `routes/web.php` - Standard module routes (URL and name prefixed with module)
     - `routes/web.backend.php` - Backend routes (auto-auth middleware, URL prefixed with `backend_prefix`, name prefixed with `backend.`)
     - `routes/web.override.php` - Override routes (URL not prefixed, but name still prefixed with module)
     - `routes/api.php` - API routes
   - Applies module-specific URL prefixes from `url_prefix` in module.json (except for override routes)
   - Route naming conventions:
     - Standard routes: `{module}.{route}`
     - Backend routes: `backend.{route}`
     - Override routes: `{module}.{route}` (URL not prefixed, name still prefixed)

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
- `Console\Commands\SynAppsSetup` - Initial setup wizard command
- `Console\Commands\SynAppsDiscover` - Module discovery command
- `Console\Commands\SynAppsPatchResources` - Resource patching command

### ConfigGenerator Subsystem

The package uses a sophisticated Builder pattern for configuration generation with lifecycle hooks.

#### Architecture

**Main Builder Class:** `VmEngine\Synapse\Services\ConfigGenerator\Builder`

The Builder orchestrates configuration generation through 5 distinct phases:

1. **init()** - Initialize builder state and load base configurations
2. **preLoop()** - Execute before module iteration
3. **loop($moduleName, $moduleConfig)** - Process each module individually
4. **postLoop()** - Execute after all modules processed
5. **finalize()** - Write compiled configurations to disk

#### Builder Base Class

**Class:** `VmEngine\Synapse\Services\ConfigGenerator\BuilderBase`

Abstract base class that all sub-builders extend. Provides lifecycle method stubs:

```php
public function init() {}
public function preLoop() {}
public function loop($packageNamespace, $packageConfig) {}
public function postLoop() {}
public function finalize() {}
```

Sub-builders override only the methods they need.

#### Sub-Builders

##### Apps Builder
**Class:** `VmEngine\Synapse\Services\ConfigGenerator\Builder\Apps`
**Lifecycle:** init, finalize

Manages application-level configuration:

- **init()**:
  - Loads `synapps/config/apps.json` (base config)
  - Creates `synapps/config/apps.dev.json` if missing
  - Merges dev overrides into base config
- **finalize()**:
  - Writes compiled config to `synapps/config/compiled/apps.json`

##### System Builder
**Class:** `VmEngine\Synapse\Services\ConfigGenerator\Builder\System`
**Lifecycle:** init, finalize

Manages system-level configuration with protection for sensitive keys:

- **init()**:
  - Loads `synapps/config/system.json` (base config)
  - Creates `synapps/config/system.dev.json` if missing
  - Filters dev config to only allow keys from `allowed_dev_keys` in config.json
  - Merges allowed dev overrides
  - Initializes default number format settings
- **finalize()**:
  - Writes config hash to `synapps/config/compiled/checksum`
  - Writes compiled config to `synapps/config/compiled/system.json`

**Number Format Defaults:**
```php
[
    'decimal_limit' => 0,
    'round_limit' => 0,
    'decimal_symbol' => ',',
    'thousands_separator_symbol' => '.'
]
```

##### Modules Builder
**Class:** `VmEngine\Synapse\Services\ConfigGenerator\Builder\Modules`
**Lifecycle:** init, finalize

Discovers and merges module configurations with override protection:

- **init()**:
  - Scans for module.json files in:
    - `synapps/modules/*/module.json`
    - `vendor/*/synmod-*/module.json`
    - `vendor/*/synmodule-*/module.json`
    - `vendor/*/synapps-*/module.json`
  - Marks modules as `is_package: 1` if in vendor, `0` otherwise
  - Sets default `load_priority: 99` if not specified
  - Sorts modules by `load_priority` (lower = loads first)
  - Loads override configs from:
    - `synapps/config/modules.json` (production overrides)
    - `synapps/config/modules.dev.json` (dev overrides)
  - Sanitizes overrides to remove protected keys
  - Merges: base → production overrides → dev overrides
- **finalize()**:
  - Writes final config to `synapps/config/compiled/modules.json`

**Protected Module Keys** (cannot be overridden):
- `id`, `namespaces`, `git`, `load_priority`, `is_package`

##### Binding Builder
**Class:** `VmEngine\Synapse\Services\ConfigGenerator\Builder\Binding`
**Lifecycle:** init, loop, postLoop, finalize

Collects and merges service container bindings from all modules:

- **init()**:
  - Loads base bindings from `config/synapse.php`
- **loop($namespace, $config)**:
  - Collects interface bindings from each module
  - Collects service providers from each module
- **postLoop()**:
  - Merges module bindings with system bindings
  - Merges collected providers into system.providers
  - Processes binding types: interface, class, alias, route
- **finalize()**:
  - Writes compiled bindings to `synapps/config/compiled/binding.json`

**Binding Structure:**
```json
{
    "interface": { "ContractName": "Implementation" },
    "class": { "AbstractClass": "ConcreteClass" },
    "alias": { "AliasName": "TargetClass" },
    "route": { "RouteName": "RouteTarget" }
}
```

#### Generation Flow

```php
$builder = new Builder();
$config = $builder->generate();
```

Internal execution order:
1. `buildConfigs()` - Initialize paths and config structure
2. `initLoop()` - Instantiate all sub-builders, call init()
3. `handlePreLoop()` - Call preLoop() on all sub-builders
4. `loopModules()` - Iterate modules, call loop() for each
5. `handlePostLoop()` - Call postLoop() on all sub-builders
6. `finalize()` - Call finalize() on all sub-builders, write files

#### Configuration Files

**Source Files:**
- `synapps/config/apps.json` - Base app config
- `synapps/config/apps.dev.json` - Dev app overrides
- `synapps/config/system.json` - Base system config
- `synapps/config/system.dev.json` - Dev system overrides (filtered)
- `synapps/config/modules.json` - Production module overrides
- `synapps/config/modules.dev.json` - Dev module overrides
- `config.json` - Protection rules (allowed/protected keys)

**Compiled Files:**
- `synapps/config/compiled/apps.json` - Final app config
- `synapps/config/compiled/system.json` - Final system config
- `synapps/config/compiled/modules.json` - Final modules config
- `synapps/config/compiled/binding.json` - Final bindings
- `synapps/config/compiled/checksum` - Config hash for validation

### Helper Services

#### Breadcrumbs
**Class:** `VmEngine\Synapse\Services\Helper\Breadcrumbs`

Fluent builder for creating breadcrumb navigation trails.

**Methods:**
- `make($label, $url = '#', $icon = null)` - Static factory method to create instance
- `add($label, $url = '#', $icon = null)` - Add breadcrumb item, returns self for chaining
- `hasItems()` - Check if breadcrumbs exist
- `items()` - Get array of breadcrumb items
- `count()` - Get number of items

**Usage:**
```php
$breadcrumbs = Breadcrumbs::make('Home', '/')
    ->add('Users', '/users')
    ->add('John Doe', '/users/1', 'fa-user');

foreach ($breadcrumbs->items() as $item) {
    // $item['label'], $item['url'], $item['icon']
}
```

#### SynapseUtils
**Class:** `VmEngine\Synapse\Services\SynapseUtils`

Utility methods for module discovery and resource scanning.

**Methods:**

##### listModuleNamespaces($namespaces, $func)
Generates list of modules by scanning given namespaces.

**Parameters:**
- `$namespaces` (array) - Format: `[NAMESPACE => [path, prefix]]`
- `$func` (Closure) - Callback: `function($namespace, $path, $moduleName, $modulePath, $namespaceValue)`

**Returns:** Array of processed modules

##### listModuleResources($namespaces, $path, $func = null)
Scans modules for specific resource directories.

**Parameters:**
- `$namespaces` (array) - Module namespaces to scan
- `$path` (string) - Relative path within modules (e.g., 'database/migrations')
- `$func` (Closure|null) - Optional callback for processing

**Returns:** Array of resource paths

##### isSynAppsAutoloaded()
Checks if SynApps namespace is registered in composer.json.

**Returns:** bool

**Usage:**
```php
$namespaces = [
    'SynApps\\Modules' => [synapps_path('modules'), false],
    'VmEngine' => [base_path('vendor/vm-engine'), 'synmod-']
];

$modules = SynapseUtils::listModuleNamespaces($namespaces, function($ns, $path, $name) {
    return $name;
});

$migrations = SynapseUtils::listModuleResources($namespaces, 'database/migrations');
```

### Helper Functions

The package loads [helper.php](packages/synapse/src/helper.php) with global utility functions:

#### Path Helpers

**`synapps_path($path = '')`**
Get absolute path to synapps directory.
```php
synapps_path('modules/example/routes/web.php')
// Returns: /app/synapps/modules/example/routes/web.php
```

**`template_assets($folder = '', $file = '')`**
Generate URL to template assets directory.
```php
template_assets('css', 'style.css')
// Returns: https://example.com/assets/template/default/css/style.css
```

#### Array Helpers

**`recuresive_array_merge(array $array1, array $array2)`**
Deep merge arrays, avoiding duplicate numeric keys.
```php
$merged = recuresive_array_merge(['a' => 1], ['b' => 2]);
```

**`array_is_list(array $arr)`**
Check if array is a list (sequential numeric keys starting from 0).

#### Routing Helpers

**`route_api_opt($appsUrl, $apiUrl)`**
Generate route options (prefix, domain) for API routes based on URL differences.

**`route_web_opt($appsUrl, $webUrl)`**
Generate route options (domain) for web routes based on URL differences.

**`is_route($routeName, $class = 'active')`**
Check if current route matches given name, return class if true.
```php
<li class="{{ is_route('users.index') }}">Users</li>
<li class="{{ is_route(['posts.create', 'posts.edit']) }}">Posts</li>
```

**`is_route_prefix($route, $class = 'active')`**
Check if current route prefix matches, return class if true.
```php
<li class="{{ is_route_prefix('admin') }}">Admin</li>
```

#### Pagination Helpers

**`pagination_format(int $count, int $offset = 1, int $limit = 10)`**
Create formatted pagination array compatible with Laravel.

**Returns:** Array with `total`, `per_page`, `current_page`, `from`, `to`

**`pagination_convert_link($url, $limit)`**
Convert Laravel page-based pagination to offset/limit format.

**`pagination_generate($paginationParam, $path, $view = 'component.pagination')`**
Generate Laravel LengthAwarePaginator from offset/limit parameters.

**Parameters:**
- `$paginationParam` - Array with keys: `data`, `count`, `offset`, `limit`
- `$path` - Base URL for pagination links
- `$view` - Pagination view template

**Returns:** `\Illuminate\Pagination\LengthAwarePaginator`

#### Number Formatting Helpers

**`is_decimal($value)`**
Check if numeric value contains decimal places.

**`clean_number_format($value, $precision = 0, $decimalSeparator = ',', $thousandSeparator = '.')`**
Format number, automatically removing unnecessary decimals.
```php
clean_number_format(1500.00)  // Returns: "1.500"
clean_number_format(1500.02, 2)  // Returns: "1.500,02"
```

#### Constants

**`DS`** - Directory separator constant (DIRECTORY_SEPARATOR)

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

## Blade Components

The package provides reusable Blade components for common UI patterns:

### Form Components

#### Checkboxes
**Class:** `VmEngine\Synapse\Components\Checkboxes`
**View:** `synapse::components.checkboxes`

Multiple checkbox inputs for array-based form data.

**Props:**
- `name` (string, required) - Input name attribute
- `options` (array, required) - Available checkbox options
- `model` (array, default: []) - Selected values

**Usage:**
```blade
<x-synapse::checkboxes
    name="permissions"
    :options="['read', 'write', 'delete']"
    :model="$selectedPermissions" />
```

#### RadioButtons
**Class:** `VmEngine\Synapse\Components\RadioButtons`
**View:** `synapse::components.radio-buttons`

Radio button group for single-selection inputs.

**Props:**
- `name` (string, required) - Input name attribute
- `options` (array, required) - Available radio options
- `model` (string, default: '') - Selected value

**Usage:**
```blade
<x-synapse::radio-buttons
    name="status"
    :options="['active', 'inactive', 'pending']"
    :model="$currentStatus" />
```

#### Select
**Class:** `VmEngine\Synapse\Components\Select`
**View:** `synapse::components.select`

Standard dropdown select component.

**Props:**
- `options` (array, required) - Select options

**Usage:**
```blade
<x-synapse::select :options="['option1', 'option2', 'option3']" />
```

#### MultiSelect
**Class:** `VmEngine\Synapse\Components\MultiSelect`
**View:** `synapse::components.multi-select`

Multiple selection dropdown with checkbox support.

**Props:**
- `name` (string, required) - Input name attribute
- `options` (array, required) - Available options
- `model` (array, default: []) - Selected values

**Usage:**
```blade
<x-synapse::multi-select
    name="tags"
    :options="$availableTags"
    :model="$selectedTags" />
```

#### Password
**Class:** `VmEngine\Synapse\Components\Password`
**View:** `synapse::components.password`

Password input with show/hide toggle functionality.

**Props:** None

**Usage:**
```blade
<x-synapse::password />
```

#### Currency
**Class:** `VmEngine\Synapse\Components\Currency`
**View:** `synapse::components.currency`

Currency input with formatting and symbol display.

**Props:**
- `currency` (string, default: 'Rp.') - Currency symbol/prefix

**Usage:**
```blade
<x-synapse::currency currency="$" />
<x-synapse::currency currency="Rp." />
```

#### Datepicker
**Class:** `VmEngine\Synapse\Components\Datepicker`
**View:** `synapse::components.datepicker`

Date picker input using Flatpickr library.

**Props:**
- `flatpickr` (string, default: '{}') - JSON configuration for Flatpickr

**Usage:**
```blade
<x-synapse::datepicker />
<x-synapse::datepicker :flatpickr='{"dateFormat": "Y-m-d", "enableTime": true}' />
```

### UI Components

#### ConfirmDialog
**Class:** `VmEngine\Synapse\Components\ConfirmDialog`
**View:** `synapse::components.confirm-dialog`

Modal confirmation dialog for destructive actions.

**Props:**
- `title` (string, default: 'Confirm Action') - Dialog title
- `message` (string, default: 'Are you sure you want to proceed?') - Confirmation message
- `confirmText` (string, default: 'Confirm') - Confirm button text
- `cancelText` (string, default: 'Cancel') - Cancel button text
- `confirmColor` (string, default: 'danger') - Confirm button color variant
- `icon` (string, default: 'fa-solid fa-circle-exclamation') - FontAwesome icon class

**Usage:**
```blade
<x-synapse::confirm-dialog
    title="Delete Item"
    message="This action cannot be undone."
    confirmText="Delete"
    confirmColor="danger"
    icon="fa-solid fa-trash" />
```

#### Drawer
**Class:** `VmEngine\Synapse\Components\Drawer`
**View:** `synapse::components.drawer`

Slide-out side panel for displaying content or forms.

**Props:**
- `title` (string, required) - Drawer header title

**Usage:**
```blade
<x-synapse::drawer title="User Settings">
    <!-- Drawer content -->
</x-synapse::drawer>
```

#### Toastr
**Class:** `VmEngine\Synapse\Components\Toastr`
**View:** `synapse::components.toastr`

Toast notification component for user feedback.

**Props:** None

**Usage:**
```blade
<x-synapse::toastr />
```

#### Alert
**Class:** `VmEngine\Synapse\Components\Alert`
**View:** `synapse::components.alert`

Flexible alert component with multiple variants and dismissible functionality using Alpine.js.

**Props:**
- `type` (string, default: 'info') - Alert type: `'success'`, `'warning'`, `'danger'`, `'error'`, `'info'`
- `title` (string, optional) - Optional alert title
- `message` (string, optional) - Alert message text (can also use slot for HTML content)
- `dismissible` (boolean, default: true) - Whether alert can be closed
- `icon` (string, auto-assigned) - FontAwesome icon class (auto-assigned based on type if not provided)
- `border` (boolean, default: false) - Show left border accent
- `solid` (boolean, default: false) - Use solid background instead of light variant

**Features:**
- Auto-assigns icons based on alert type
- Smooth show/hide animations with Alpine.js
- Full dark mode support
- Supports both props and slot content
- Border and solid style variants
- ARIA accessibility attributes

**Default Icons by Type:**
- Success: `fa-solid fa-circle-check`
- Warning: `fa-solid fa-triangle-exclamation`
- Danger/Error: `fa-solid fa-circle-exclamation`
- Info: `fa-solid fa-circle-info`

**Usage:**
```blade
<!-- Basic alert -->
<x-synapse::alert type="success" message="Operation completed successfully!" />

<!-- Alert with title and message -->
<x-synapse::alert
    type="warning"
    title="Warning"
    message="Please review your changes before submitting." />

<!-- Non-dismissible alert -->
<x-synapse::alert
    type="danger"
    :dismissible="false"
    message="This is a critical error that requires attention." />

<!-- Border accent style -->
<x-synapse::alert
    type="info"
    :border="true"
    message="Information with border accent" />

<!-- Solid background style -->
<x-synapse::alert
    type="success"
    :solid="true"
    message="Solid background variant" />

<!-- Custom HTML content with slot -->
<x-synapse::alert type="info" title="Getting Started">
    <p>Welcome! Check out <a href="/docs" class="underline">our documentation</a>.</p>
    <ul class="list-disc list-inside mt-2">
        <li>Feature 1</li>
        <li>Feature 2</li>
    </ul>
</x-synapse::alert>

<!-- Custom icon -->
<x-synapse::alert
    type="success"
    icon="fa-solid fa-check-double"
    message="Verified successfully!" />

<!-- Flash messages -->
@if(session('success'))
    <x-synapse::alert type="success" :message="session('success')" />
@endif

<!-- Validation errors -->
@if($errors->any())
    <x-synapse::alert type="danger" title="Validation Errors">
        <ul class="list-disc list-inside space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-synapse::alert>
@endif
```

## IDE Helper Generator

**Script:** `generate-ide-helper.php`
**Location:** Package root

Generates PHPDoc annotations for better IDE autocompletion by analyzing facades, models, and helper functions.

### Usage

```bash
# Basic usage (from Laravel root)
cd /path/to/laravel
php packages/synapse/generate-ide-helper.php

# With additional model paths
php packages/synapse/generate-ide-helper.php \
  --scan-models=synapps/modules/home/Models \
  --scan-models=synapps/modules/users/Models

# With helper files
php packages/synapse/generate-ide-helper.php \
  --scan-helpers=app/helpers.php

# Custom output location
php packages/synapse/generate-ide-helper.php \
  --output=/custom/path/_ide_helper.php

# Show help
php packages/synapse/generate-ide-helper.php --help
```

### What It Does

1. **Scans for Models** (default paths):
   - `./src` (package models)
   - `./app/Models` (Laravel app models - automatic!)
   - `./vendor/laravel/framework/.../Auth` (Laravel auth models)

2. **Scans for Helpers** (default paths):
   - `./src/helper.php` (package helpers)

3. **Generates PHPDoc for**:
   - All Laravel core facades (App, Artisan, Auth, Blade, Cache, etc.)
   - Livewire facade (if installed)
   - Eloquent models with query builder methods
   - Helper functions with parameter signatures

4. **Outputs**: `_ide_helper.php` in current working directory

### Command Line Options

- `--scan-models=<path>` - Additional model directories (comma-separated or multiple flags)
- `--scan-helpers=<path>` - Additional helper files (comma-separated or multiple flags)
- `--output=<file>` - Output file path (default: `_ide_helper.php`)
- `--help` - Show help message

### Output Location Behavior

The generated file is placed in the **current working directory** (where you run the command), using `getcwd()` instead of script directory. This is intentional and follows standard CLI tool conventions.

**Recommended:** Always run from Laravel root:
```bash
cd /path/to/laravel
php packages/synapse/generate-ide-helper.php
# Creates: /path/to/laravel/_ide_helper.php
```

### Features (V2)

- ✅ Automatically scans `app/Models` (no manual specification needed)
- ✅ Output placed in current directory (intuitive CLI behavior)
- ✅ Supports multiple model paths via flags or comma-separated
- ✅ Supports multiple helper files
- ✅ Custom output location via `--output`
- ✅ Silent skipping of missing default paths (no noise)
- ✅ Comprehensive help via `--help`

See [IDE_HELPER.md](IDE_HELPER.md) for complete documentation.

## Testing

The package includes comprehensive test coverage using **PestPHP 3.x**.

### Test Structure

```
tests/
├── Feature/                          # Integration tests
│   ├── Commands/
│   │   └── SynAppsCommandsTest.php
│   └── ModuleDiscoveryTest.php
├── Unit/                             # Unit tests
│   ├── Components/
│   │   └── BladeComponentsTest.php
│   ├── Services/
│   │   ├── Helper/
│   │   │   └── BreadcrumbsTest.php
│   │   ├── ConfigGenerator/
│   │   │   ├── BuilderTest.php
│   │   │   └── BuilderBaseTest.php
│   │   ├── ConfigGeneratorsTest.php
│   │   └── SynapseUtilsTest.php
│   ├── HelperFunctionsTest.php
│   └── ServiceProvidersTest.php
├── Pest.php
└── TestCase.php
```

### Running Tests

```bash
# All tests
composer test

# With coverage
composer test-coverage

# Specific suite
./vendor/bin/pest tests/Unit
./vendor/bin/pest tests/Feature

# Specific file
./vendor/bin/pest tests/Unit/HelperFunctionsTest.php

# Filter by name
./vendor/bin/pest --filter="breadcrumbs"
```

### Test Coverage (400+ tests)

**Unit Tests:**
- Helper functions (195+ tests) - All global helpers in `src/helper.php`
- Breadcrumbs service (16 tests) - Navigation builder
- SynapseUtils (23 tests) - Utility methods
- ConfigGenerator builders (21 tests) - Config generation system
- Config generators (30 tests) - SynapseConfigGenerator & SynAppsConfigGenerator
- Blade components (70+ tests) - All 10 UI components
- Service providers (10 tests) - Provider registration

**Feature Tests:**
- Artisan commands (9 tests) - synapps:patch-resources, synapps:discover, synapps:setup
- Module discovery (23 tests) - Integration tests for module system

**Test Infrastructure:**
- Uses **Orchestra Testbench** for package testing
- In-memory SQLite for fast database tests
- Helper methods in TestCase for module creation/cleanup
- GitHub Actions workflow ready

### Test Configuration

- `phpunit.xml` - PHPUnit/Pest configuration
- `tests/Pest.php` - Pest setup and expectations
- `tests/TestCase.php` - Base test case with helpers
- Coverage target: ~85%

See [TESTING.md](TESTING.md) for complete testing documentation.

## Dependencies

- Laravel Framework 12.x
- Livewire 3.x
- composer/semver ^3.0 (for dependency version comparison)

### Dev Dependencies

- pestphp/pest ^3.0
- pestphp/pest-plugin-laravel ^3.0
- orchestra/testbench ^10.0
- laradumps/laradumps ^4.0
