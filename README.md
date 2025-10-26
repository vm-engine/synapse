# Synapse Modular Engine

A sophisticated modular architecture system for Laravel applications that enables both directory-based and package-based modules with automatic discovery, dependency management, and configuration generation.

## Features

- **Dual Module System**: Support for directory-based (`synapps/modules/`) and package-based (`vendor/*/synmod-*`) modules
- **Automatic Discovery**: Intelligent module and dependency detection
- **Configuration Management**: Builder pattern with lifecycle hooks for config generation
- **Blade Components**: Pre-built UI components for forms and common patterns
- **Route Management**: Automatic route registration with module-specific prefixes
- **Service Container Integration**: Module-based bindings and service providers

## Installation

Since this is a private package, you need to manually add it to your `composer.json`:

### 1. Add Repository to composer.json

Add the private git repository to your `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://repo.4visionmedia.com:4000/vm-engine/synapse.git"
        }
    ]
}
```

### 2. Require the Package

Add the package to your requirements:

```json
{
    "require": {
        "vm-engine/synapse": "@dev"
    }
}
```

### 3. Install via Composer

Run composer install:

```bash
composer install
```

> **Note**: You may need to configure your Git credentials to access the private repository.

## Setup Guide

Follow these steps to set up Synapse in your Laravel project:

### 1. Initialize SynApps Directory

**Option A: New Project Setup**

Run the interactive setup wizard:

```bash
php artisan synapps:setup
```

This will prompt you for:
- App code (snake-case identifier)
- App name
- Author information
- Repository URL
- Domain URLs (dev/prod)
- URL prefixes (frontend/API)
- Application title and description

**Option B: Existing SynApps Repository**

If you have an existing SynApps repository, clone it to the `synapps/` folder:

```bash
git clone <your-synapps-repo-url> synapps
```

> **Skip this step** if your Laravel project is already a Synapse project.

### 2. Publish Synapse Configuration

Publish the Synapse configuration files:

```bash
php artisan vendor:publish --tag="synapse-config"
```

This publishes:
- `config/synapse.php` - Core Synapse configuration
- `config/synapps.php` - Application configuration

### 3. Discover Modules and Dependencies

Run the discovery command to scan modules and update dependencies:

```bash
php artisan synapps:discover
```

This command will:
- Discover all modules in `synapps/modules/`
- Auto-generate PSR-4 namespaces in `composer.json`
- Scan and merge npm/composer dependencies
- Run `composer dump-autoload` or `composer update`
- Run `npm install` if needed
- Compile assets if dependencies changed

### 4. Cache Configuration

Cache the generated configurations:

```bash
php artisan config:cache
```

### 5. Run Database Migrations

If you're using the example module or have module migrations:

```bash
php artisan migrate
```

This creates database tables defined in module migrations.

### 6. Patch Resource Files

Integrate Synapse resources into your Laravel app:

```bash
php artisan synapps:patch-resources
```

This adds import statements to:
- `resources/js/app.js` - Imports Synapse JavaScript
- `resources/css/app.css` - Imports Synapse styles

### 7. Clean Up Default Routes (Fresh Laravel Only)

If you're using a fresh Laravel installation, remove the default route from `routes/web.php`:

```php
// Delete this:
Route::get('/', function () {
    return view('welcome');
});
```

Module routes will handle all routing automatically.

### 8. Compile Assets

Build your frontend assets:

```bash
npm run build
```

For development with hot reload:

```bash
npm run dev
```

## Using Synapse Components

Synapse provides pre-built Blade components for common UI patterns. All components use the `synapse::` namespace.

### Form Components

#### Checkboxes

Multiple checkbox inputs for array-based selections:

```blade
<x-synapse::checkboxes
    name="permissions"
    :options="['read' => 'Read', 'write' => 'Write', 'delete' => 'Delete']"
    :model="$selectedPermissions" />
```

**Props:**
- `name` (string, required) - Input name
- `options` (array, required) - Available options
- `model` (array, default: []) - Selected values

#### Radio Buttons

Radio button group for single selection:

```blade
<x-synapse::radio-buttons
    name="status"
    :options="['active' => 'Active', 'inactive' => 'Inactive']"
    :model="$currentStatus" />
```

**Props:**
- `name` (string, required) - Input name
- `options` (array, required) - Available options
- `model` (string, default: '') - Selected value

#### Select Dropdown

Standard dropdown select:

```blade
<x-synapse::select
    :options="['option1' => 'Option 1', 'option2' => 'Option 2']" />
```

**Props:**
- `options` (array, required) - Select options

#### Multi-Select

Multiple selection dropdown:

```blade
<x-synapse::multi-select
    name="tags"
    :options="$availableTags"
    :model="$selectedTags" />
```

**Props:**
- `name` (string, required) - Input name
- `options` (array, required) - Available options
- `model` (array, default: []) - Selected values

#### Password Input

Password field with show/hide toggle:

```blade
<x-synapse::password />
```

#### Currency Input

Currency input with formatting:

```blade
<x-synapse::currency currency="$" />
<x-synapse::currency currency="Rp." />
```

**Props:**
- `currency` (string, default: 'Rp.') - Currency symbol

#### Datepicker

Date picker using Flatpickr:

```blade
<x-synapse::datepicker />

<!-- With custom Flatpickr config -->
<x-synapse::datepicker
    :flatpickr='{"dateFormat": "Y-m-d", "enableTime": true}' />
```

**Props:**
- `flatpickr` (string, default: '{}') - JSON configuration for Flatpickr

### UI Components

#### Confirm Dialog

Modal confirmation for destructive actions:

```blade
<x-synapse::confirm-dialog
    title="Delete Item"
    message="This action cannot be undone. Are you sure?"
    confirmText="Delete"
    cancelText="Cancel"
    confirmColor="danger"
    icon="fa-solid fa-trash" />
```

**Props:**
- `title` (string, default: 'Confirm Action')
- `message` (string, default: 'Are you sure you want to proceed?')
- `confirmText` (string, default: 'Confirm')
- `cancelText` (string, default: 'Cancel')
- `confirmColor` (string, default: 'danger')
- `icon` (string, default: 'fa-solid fa-circle-exclamation')

#### Drawer

Slide-out side panel:

```blade
<x-synapse::drawer title="Settings">
    <!-- Drawer content here -->
    <form>
        <!-- Form fields -->
    </form>
</x-synapse::drawer>
```

**Props:**
- `title` (string, required) - Drawer header title

#### Toastr Notifications

Toast notifications for user feedback:

```blade
<x-synapse::toastr />
```

Place this component in your layout file to enable toast notifications throughout your application.

#### Alert

Flexible alert component with dismissible functionality:

```blade
<!-- Basic alert -->
<x-synapse::alert type="success" message="Operation completed successfully!" />

<!-- Alert with title -->
<x-synapse::alert
    type="warning"
    title="Warning"
    message="Please review your changes before submitting." />

<!-- Non-dismissible alert -->
<x-synapse::alert
    type="danger"
    :dismissible="false"
    message="This is a critical error." />

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

<!-- Custom HTML content using slot -->
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
```

**Props:**
- `type` (string, default: 'info') - Alert type: `'success'`, `'warning'`, `'danger'`, `'error'`, `'info'`
- `title` (string, optional) - Alert title
- `message` (string, optional) - Alert message (can also use slot for HTML)
- `dismissible` (boolean, default: true) - Whether alert can be closed
- `icon` (string, auto-assigned) - FontAwesome icon class (auto-assigned based on type)
- `border` (boolean, default: false) - Show left border accent
- `solid` (boolean, default: false) - Use solid background instead of light variant

**Features:**
- 4 alert types with auto-assigned icons (success, warning, danger, info)
- Dismissible with Alpine.js smooth animations
- Dark mode support
- Supports both props and slot content
- Border and solid style variants
- Accessible with ARIA attributes

**Default Icons:**
- Success: `fa-solid fa-circle-check`
- Warning: `fa-solid fa-triangle-exclamation`
- Danger/Error: `fa-solid fa-circle-exclamation`
- Info: `fa-solid fa-circle-info`

**Common Use Cases:**

```blade
<!-- Flash messages -->
@if(session('success'))
    <x-synapse::alert type="success" :message="session('success')" />
@endif

@if(session('error'))
    <x-synapse::alert type="danger" :message="session('error')" />
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

<!-- Multiple alerts -->
<div class="space-y-4">
    <x-synapse::alert type="success" message="Payment received." />
    <x-synapse::alert type="warning" message="Subscription expires in 3 days." />
    <x-synapse::alert type="info" message="New features available." />
</div>
```

## Helper Functions

Synapse provides global helper functions for common tasks:

### Path Helpers

```php
// Get path to synapps directory
synapps_path('modules/example/routes/web.php');
// Returns: /app/synapps/modules/example/routes/web.php

// Generate URL to template assets
template_assets('css', 'style.css');
// Returns: https://example.com/assets/template/default/css/style.css
```

### Routing Helpers

```php
// Check if current route matches
<li class="{{ is_route('users.index') }}">Users</li>
<li class="{{ is_route(['posts.create', 'posts.edit']) }}">Posts</li>

// Check route prefix
<li class="{{ is_route_prefix('admin') }}">Admin</li>
```

### Number Formatting

```php
// Check if value has decimals
is_decimal(1500.02); // true
is_decimal(1500); // false

// Clean number formatting (removes unnecessary decimals)
clean_number_format(1500.00);  // "1.500"
clean_number_format(1500.02, 2);  // "1.500,02"
```

### Array Helpers

```php
// Deep merge arrays
$merged = recuresive_array_merge(['a' => 1], ['b' => 2]);

// Check if array is a list
array_is_list([1, 2, 3]); // true
array_is_list(['a' => 1]); // false
```

## Creating Modules

### Directory-based Module Structure

```
synapps/modules/{module-name}/
├── module.json              # Required: module configuration
├── src/
│   ├── {ModuleName}ServiceProvider.php
│   ├── Livewire/           # Livewire components
│   └── Models/             # Eloquent models
├── routes/
│   ├── web.php             # Web routes
│   └── api.php             # API routes
├── resources/
│   └── views/
│       ├── components/     # Blade components
│       └── livewire/       # Livewire views
└── database/
    ├── migrations/
    ├── factories/
    └── seeders/
```

### module.json Schema

```json
{
    "id": "example",
    "id_case": {
        "snake": "example",
        "pascal": "Example",
        "slug": "example",
        "studly": "example"
    },
    "name": "Example Module",
    "description": "Module description",
    "version": "0.1.0",
    "author": "Author Name",
    "email": "author@example.com",
    "url_prefix": "example",
    "load_priority": 99,
    "dependencies": {
        "npm": {
            "main": {},
            "dev": {}
        },
        "composer": {
            "main": {},
            "dev": {}
        }
    }
}
```

### Creating a New Module

1. Create directory structure in `synapps/modules/{module-name}/`
2. Create `module.json` with configuration
3. Create ServiceProvider in `src/{ModuleName}ServiceProvider.php`
4. Run `php artisan synapps:discover` to register namespaces
5. Run `php artisan config:cache`

## Artisan Commands

### synapps:setup
Interactive setup wizard for new installations.

```bash
php artisan synapps:setup
```

### synapps:discover
Discover modules and manage dependencies.

```bash
php artisan synapps:discover
```

### synapps:patch-resources
Integrate Synapse resources into Laravel.

```bash
php artisan synapps:patch-resources
```

## Module Namespaces

Each directory-based module uses three namespaces:

- **Application**: `SynApps\Modules\{ModuleName}\`
- **Seeders**: `SynDB\Modules\{ModuleName}\Seeders\`
- **Factories**: `SynDB\Modules\{ModuleName}\Factories\`

These are automatically registered by `synapps:discover`.

## Configuration Files

### Source Configuration

- `synapps/config/apps.json` - Application configuration
- `synapps/config/apps.dev.json` - Development overrides
- `synapps/config/system.json` - System configuration
- `synapps/config/system.dev.json` - System dev overrides
- `synapps/config/modules.json` - Module overrides
- `synapps/config/modules.dev.json` - Module dev overrides

### Compiled Configuration

Generated by the config builders:

- `synapps/config/compiled/apps.json`
- `synapps/config/compiled/system.json`
- `synapps/config/compiled/modules.json`
- `synapps/config/compiled/binding.json`
- `synapps/config/compiled/checksum`

## Package-based Modules

Create Composer packages with:
- Package name prefix: `synmod-`, `synmodule-`, or `synapps-`
- Include `module.json` in package root
- Use Laravel package auto-discovery for service providers

## Requirements

- PHP 8.2+
- Laravel 12.x
- Livewire 3.x
- Composer
- Node.js & NPM

## License

This package is proprietary software developed by VM Engine.

## Support

For issues and questions, please contact the VM Engine development team.

## IDE Helper Generator

Generate PHPDoc annotations for better IDE autocompletion:

```bash
# Navigate to Laravel root
cd /path/to/laravel

# Generate IDE helper
php packages/synapse/generate-ide-helper.php
```

**What it does:**
- ✅ Scans `packages/synapse/src` for package models
- ✅ Scans `app/Models` for application models (automatic!)
- ✅ Generates PHPDoc for Laravel facades
- ✅ Outputs to `_ide_helper.php` in current directory

**For modular apps:**
```bash
php packages/synapse/generate-ide-helper.php \
  --scan-models=synapps/modules/home/Models \
  --scan-models=synapps/modules/users/Models
```

See [IDE_HELPER.md](IDE_HELPER.md) for complete documentation.

## Testing

This package includes comprehensive test coverage using PestPHP:

```bash
# Run all tests
composer test

# Run with coverage
composer test-coverage

# Run specific test suite
./vendor/bin/pest tests/Unit
./vendor/bin/pest tests/Feature
```

**Test Coverage:**
- Helper functions (195+ tests)
- Services (Breadcrumbs, SynapseUtils, ConfigGenerators)
- Blade components (all 10 components)
- Service providers
- Artisan commands
- Module discovery

See [TESTING.md](TESTING.md) for complete testing documentation.

## Additional Documentation

For detailed technical documentation, see:
- **[CLAUDE.md](CLAUDE.md)** - Architecture, ConfigGenerator, Builder pattern, Service providers
- **[IDE_HELPER.md](IDE_HELPER.md)** - IDE helper generator complete guide
- **[TESTING.md](TESTING.md)** - Comprehensive testing guide with 400+ test cases
