# Synapse Module Generator

Complete Laravel-style code generator for Synapse modular architecture.

## Features

- ✅ **17 Artisan Commands** - Mirror all Laravel make commands
- ✅ **Dual Module Support** - Directory modules (`synapps/modules/`) and package modules (`vendor/*/synmod-*`)
- ✅ **Standalone Executable** - `synmod-make` for package developers (no Laravel required)
- ✅ **Smart Namespacing** - Auto-detects module type and generates correct namespaces
- ✅ **30+ Stubs** - Complete template library for all component types
- ✅ **Cross-Platform** - Works on Windows, Linux, and macOS

## Table of Contents

- [Installation](#installation)
- [Directory Modules](#directory-modules)
- [Package Modules](#package-modules)
- [Available Commands](#available-commands)
- [Generated Structure](#generated-structure)
- [Namespace Patterns](#namespace-patterns)
- [Windows Compatibility](#windows-compatibility)
- [Examples](#examples)

---

## Installation

The module generator is included with Synapse. No additional installation required.

```bash
# Verify commands are available
php artisan list synapps:make
```

---

## Directory Modules

Directory modules are located in `synapps/modules/` within your Laravel project.

### Creating a Module

```bash
php artisan synapps:make-module blog --author="Your Name" --email="you@example.com"
```

**Interactive Mode:**
```bash
php artisan synapps:make-module blog
# Will prompt for: author, email, description, URL prefix, etc.
```

### Generating Components

```bash
# Model with migration, factory, and seeder
php artisan synapps:make-model blog Post -mfs

# Resource controller
php artisan synapps:make-controller blog PostController --resource

# Livewire component
php artisan synapps:make-livewire blog PostList

# Form request validation
php artisan synapps:make-request blog StorePostRequest

# Migration
php artisan synapps:make-migration blog create_posts_table --create=posts

# And more...
```

### Namespace Structure

Directory modules use the `SynApps\Modules\{ModuleName}` namespace:

- **Models**: `SynApps\Modules\Blog\Models\Post`
- **Controllers**: `SynApps\Modules\Blog\Http\Controllers\PostController`
- **Livewire**: `SynApps\Modules\Blog\Livewire\PostList`
- **Factories**: `SynDB\Modules\Blog\Factories\PostFactory`
- **Seeders**: `SynDB\Modules\Blog\Seeders\PostSeeder`

---

## Package Modules

Package modules are standalone Composer packages for distribution.

### Setup

```bash
# 1. Create package directory
mkdir packages/synmod-myblog
cd packages/synmod-myblog

# 2. Initialize composer.json
composer init --name=vm-engine/synmod-myblog --type=synapse-module

# 3. Add Synapse dependency
composer require vm-engine/synapse:@dev
```

### Using the Standalone Executable

```bash
# Generate components without Laravel
./vendor/bin/synmod-make model Post
./vendor/bin/synmod-make controller PostController --resource
./vendor/bin/synmod-make livewire PostList
./vendor/bin/synmod-make factory PostFactory
./vendor/bin/synmod-make migration create_posts_table --create=posts

# View help
./vendor/bin/synmod-make --help
```

### Namespace Structure

Package modules use vendor-based namespaces:

For package `vm-engine/synmod-blog`:
- **Models**: `VmEngine\Blog\Models\Post`
- **Controllers**: `VmEngine\Blog\Http\Controllers\PostController`
- **Livewire**: `VmEngine\Blog\Livewire\PostList`
- **Factories**: `VmEngine\Blog\Factories\PostFactory`
- **Seeders**: `VmEngine\Blog\Seeders\PostSeeder`

---

## Available Commands

### Module Creation
| Command | Description |
|---------|-------------|
| `synapps:make-module` | Create complete module structure |

### Component Generators
| Command | Description | Options |
|---------|-------------|---------|
| `synapps:make-model` | Create model | `-m` migration, `-f` factory, `-s` seeder, `-a` all |
| `synapps:make-controller` | Create controller | `--resource`, `--api`, `--invokable` |
| `synapps:make-migration` | Create migration | `--create={table}`, `--table={table}` |
| `synapps:make-factory` | Create factory | |
| `synapps:make-seeder` | Create seeder | |
| `synapps:make-livewire` | Create Livewire component | `--inline` |
| `synapps:make-request` | Create form request | |
| `synapps:make-middleware` | Create middleware | |
| `synapps:make-event` | Create event | |
| `synapps:make-listener` | Create listener | |
| `synapps:make-job` | Create job | |
| `synapps:make-provider` | Create service provider | |
| `synapps:make-policy` | Create policy | |
| `synapps:make-notification` | Create notification | |
| `synapps:make-mail` | Create mailable | |
| `synapps:make-test` | Create test | `--unit` |

---

## Generated Structure

```
{module}/
├── database/
│   ├── factories/       # Model factories
│   ├── migrations/      # Database migrations
│   └── seeders/         # Database seeders
├── lang/
│   └── en/              # Language files
│       ├── acl.php
│       └── menu.php
├── resources/
│   └── views/           # Blade views
│       └── livewire/    # Livewire views
├── routes/
│   ├── web.php          # Frontend routes
│   ├── web.backend.php  # Backend routes (auto-auth)
│   └── api.php          # API routes
├── src/
│   ├── Events/          # Event classes
│   ├── Http/
│   │   ├── Controllers/ # HTTP controllers
│   │   ├── Middleware/  # Middleware
│   │   └── Requests/    # Form requests
│   ├── Jobs/            # Queue jobs
│   ├── Listeners/       # Event listeners
│   ├── Livewire/        # Livewire components
│   ├── Mail/            # Mailable classes
│   ├── Models/          # Eloquent models
│   ├── Notifications/   # Notifications
│   ├── Policies/        # Authorization policies
│   └── Providers/       # Service providers
├── tests/
│   ├── Feature/         # Feature tests
│   └── Unit/            # Unit tests
├── module.json          # Module configuration
└── {Module}ServiceProvider.php
```

---

## Namespace Patterns

### Directory Modules

```
Base:       SynApps\Modules\{ModuleName}
Models:     SynApps\Modules\{ModuleName}\Models
Controllers: SynApps\Modules\{ModuleName}\Http\Controllers
Livewire:   SynApps\Modules\{ModuleName}\Livewire
Events:     SynApps\Modules\{ModuleName}\Events
Jobs:       SynApps\Modules\{ModuleName}\Jobs
Factories:  SynDB\Modules\{ModuleName}\Factories
Seeders:    SynDB\Modules\{ModuleName}\Seeders
```

### Package Modules

```
Base:       {Vendor}\{ModuleName}
Models:     {Vendor}\{ModuleName}\Models
Controllers: {Vendor}\{ModuleName}\Http\Controllers
Livewire:   {Vendor}\{ModuleName}\Livewire
Events:     {Vendor}\{ModuleName}\Events
Jobs:       {Vendor}\{ModuleName}\Jobs
Factories:  {Vendor}\{ModuleName}\Factories
Seeders:    {Vendor}\{ModuleName}\Seeders
```

**Vendor Conversion:**
- `vm-engine/synmod-blog` → Vendor: `VmEngine`, Module: `Blog`
- `acme-corp/synmod-shop` → Vendor: `AcmeCorp`, Module: `Shop`

---

## Windows Compatibility

**✅ Full Windows Support** - All commands work on Windows without modifications.

### Artisan Commands
```powershell
# Windows PowerShell / Command Prompt
php artisan synapps:make-module shop
php artisan synapps:make-model shop Product -mfs
php artisan synapps:make-controller shop ProductController --resource
```

### Standalone Executable

Composer automatically creates Windows batch wrappers:

```powershell
# Windows - both work
vendor\bin\synmod-make model Product
php vendor/bin/synmod-make model Product
```

**How it works:**
- Composer creates `vendor/bin/synmod-make.bat` on Windows
- Composer creates `vendor/bin/synmod-make` on Unix/Linux/Mac
- Both point to the same PHP script with platform-appropriate wrappers

---

## Examples

### Complete CRUD Scaffold

```bash
# 1. Create module
php artisan synapps:make-module blog \
  --author="John Doe" \
  --email="john@example.com" \
  --url-prefix=blog

# 2. Generate model with all dependencies
php artisan synapps:make-model blog Post -mfs

# 3. Generate controller
php artisan synapps:make-controller blog PostController --resource --model=Post

# 4. Generate Livewire components
php artisan synapps:make-livewire blog PostList
php artisan synapps:make-livewire blog PostForm

# 5. Generate form requests
php artisan synapps:make-request blog StorePostRequest
php artisan synapps:make-request blog UpdatePostRequest

# 6. Generate policy
php artisan synapps:make-policy blog PostPolicy

# 7. Generate tests
php artisan synapps:make-test blog PostTest
```

### Package Development

```bash
# 1. Setup package
cd packages/synmod-shop
composer require vm-engine/synapse:@dev

# 2. Generate Product model
./vendor/bin/synmod-make model Product

# 3. Generate migration
./vendor/bin/synmod-make migration create_products_table --create=products

# 4. Generate factory
./vendor/bin/synmod-make factory ProductFactory

# 5. Generate seeder
./vendor/bin/synmod-make seeder ProductSeeder

# 6. Generate controller
./vendor/bin/synmod-make controller ProductController --resource

# 7. Generate Livewire catalog
./vendor/bin/synmod-make livewire ProductCatalog

# 8. Generate policy
./vendor/bin/synmod-make policy ProductPolicy

# 9. Generate tests
./vendor/bin/synmod-make test ProductTest
```

### API Development

```bash
# API-focused components
php artisan synapps:make-controller api ProductApiController --api
php artisan synapps:make-request api StoreProductRequest
php artisan synapps:make-request api UpdateProductRequest
php artisan synapps:make-policy api ProductPolicy
```

### Event-Driven Architecture

```bash
# Events and listeners
php artisan synapps:make-event orders OrderCreated
php artisan synapps:make-listener orders SendOrderConfirmation
php artisan synapps:make-listener orders UpdateInventory
php artisan synapps:make-job orders ProcessOrder
php artisan synapps:make-notification orders OrderShipped
php artisan synapps:make-mail orders OrderConfirmationMail
```

---

## Advanced Usage

### Custom Base Path

```php
// For custom module locations
php artisan synapps:make-model blog Post --path=/custom/path/to/modules
```

### Non-Interactive Mode

All commands support `--no-interaction` for scripting:

```bash
php artisan synapps:make-module shop \
  --author="Dev Team" \
  --email="dev@company.com" \
  --url-prefix=shop \
  --no-interaction
```

### Multiple Variants

```bash
# Controllers
php artisan synapps:make-controller blog PostController          # Plain
php artisan synapps:make-controller blog PostController --resource    # Resource
php artisan synapps:make-controller blog PostApiController --api      # API
php artisan synapps:make-controller blog ShowPostController --invokable  # Invokable

# Livewire
php artisan synapps:make-livewire blog PostList               # Standard
php artisan synapps:make-livewire blog QuickPost --inline     # Inline

# Migrations
php artisan synapps:make-migration blog create_posts_table --create=posts
php artisan synapps:make-migration blog add_slug_to_posts --table=posts

# Models
php artisan synapps:make-model blog Post                # Plain
php artisan synapps:make-model blog Post -m             # With migration
php artisan synapps:make-model blog Post -mf            # With migration & factory
php artisan synapps:make-model blog Post -mfs           # With all
php artisan synapps:make-model blog PostCategory --pivot # Pivot model

# Tests
php artisan synapps:make-test blog PostFeatureTest      # Feature test
php artisan synapps:make-test blog PostTest --unit      # Unit test
```

---

## Troubleshooting

### Command Not Found

```bash
# Verify Synapse is installed
composer show vm-engine/synapse

# Clear cache
php artisan config:clear
php artisan cache:clear

# List available commands
php artisan list synapps:make
```

### Windows Executable Issues

```powershell
# If ./vendor/bin/synmod-make doesn't work, use:
php vendor/bin/synmod-make model Product

# Or use full path:
php C:\path\to\project\vendor\bin\synmod-make model Product
```

### Namespace Issues

Ensure your module is registered:
```bash
# Regenerate module discovery
php artisan synapps:discover

# This updates composer autoload with module namespaces
```

---

## Architecture

### Core Services

1. **NamespaceResolver** - Handles complex 3-namespace structure
2. **StubRenderer** - Template rendering with variable replacement
3. **ModuleGeneratorService** - Main orchestrator for code generation

### Stub System

Templates located in `stubs/module-generator/`:
- `module/` - Module configuration
- `model/` - Model variants (standard, pivot)
- `controller/` - Controller variants (plain, resource, API, invokable)
- `migration/` - Migration variants (blank, create, update)
- `livewire/` - Livewire variants (standard, inline, view)
- And 20+ more component types...

---

## Contributing

When adding new commands:

1. Create command class extending `BaseGeneratorCommand`
2. Create corresponding stub file(s)
3. Register command in `SynapseServiceProvider`
4. Add tests

---

## License

Part of the Synapse package. Licensed under MIT.

---

**The Synapse Module Generator provides complete Laravel-style code generation for modular architecture development!** 🎉
