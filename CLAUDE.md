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

## Dependencies

- Laravel Framework 12.x
- Livewire 3.x
- composer/semver ^3.0 (for dependency version comparison)
