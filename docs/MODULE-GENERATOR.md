# Synapse Module Generator

Complete Laravel-style code generator for Synapse modular architecture.

## Features

- ✅ **31 Artisan Commands** - Complete Laravel make command coverage + unified `synmod-make`
- ✅ **Dual Module Support** - Directory modules (`synapps/modules/`) and package modules (`vendor/*/synmod-*`)
- ✅ **Unified Interface** - `synmod-make` command with interactive menu for all component types
- ✅ **Smart Namespacing** - Auto-detects module type and generates correct namespaces
- ✅ **45+ Stubs** - Complete template library for all component types
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

### Unified Command
| Command | Description |
|---------|-------------|
| `synmod-make` | **Standalone bin executable** - Create any component type with interactive menu (use `./vendor/bin/synmod-make`) |

### Module Creation
| Command | Description |
|---------|-------------|
| `synapps:make-module` | Create complete module structure |

### Component Generators
| Command | Description | Options |
|---------|-------------|---------|
| `synapps:make-cast` | Create custom Eloquent cast | |
| `synapps:make-channel` | Create broadcasting channel | |
| `synapps:make-command` | Create Artisan command | `--command={name}` |
| `synapps:make-component` | Create Blade view component | `--inline` |
| `synapps:make-config` | Create config file | |
| `synapps:make-controller` | Create controller | `--resource`, `--api`, `--invokable` |
| `synapps:make-enum` | Create PHP enum | |
| `synapps:make-event` | Create event | |
| `synapps:make-factory` | Create factory | |
| `synapps:make-interface` | Create interface | |
| `synapps:make-job` | Create job | |
| `synapps:make-job-middleware` | Create job middleware | |
| `synapps:make-listener` | Create listener | |
| `synapps:make-livewire` | Create Livewire component | `--inline`, `--test`, `--mfc`, `--sfc` |
| `synapps:make-mail` | Create mailable | |
| `synapps:make-middleware` | Create middleware | |
| `synapps:make-migration` | Create migration | `--create={table}`, `--table={table}` |
| `synapps:make-model` | Create model | `-m` migration, `-f` factory, `-s` seeder, `-a` all, `--pivot` |
| `synapps:make-notification` | Create notification | |
| `synapps:make-observer` | Create observer | `--model={Model}` |
| `synapps:make-policy` | Create policy | |
| `synapps:make-provider` | Create service provider | |
| `synapps:make-request` | Create form request | |
| `synapps:make-resource` | Create API resource | `--collection` |
| `synapps:make-rule` | Create validation rule | |
| `synapps:make-scope` | Create query scope | |
| `synapps:make-seeder` | Create seeder | |
| `synapps:make-test` | Create test | `--unit` |
| `synapps:make-trait` | Create trait | |
| `synapps:make-view` | Create Blade view | |

---

## Livewire Component Formats

`synapps:make-livewire` (and the `livewire` sub-command in `synmod-make`) supports three component formats for Livewire v4:

### Standard (class-based)

Creates a class in `src/Livewire/` with a separate view in `resources/views/livewire/`:

```bash
php artisan synapps:make-livewire blog PostList
# → src/Livewire/PostList.php
# → resources/views/livewire/post-list.blade.php
```

Usage: `<livewire:blog::post-list />`

### MFC — Multi-File Component (`--mfc`)

Creates a directory under `resources/views/components/` containing a PHP anonymous class file and a Blade template. Supports nested paths:

```bash
php artisan synapps:make-livewire blog PostList --mfc
# → resources/views/components/⚡post-list/post-list.php
# → resources/views/components/⚡post-list/post-list.blade.php

# Nested path (slash-separated)
php artisan synapps:make-livewire blog Forms/ContactForm --mfc
# → resources/views/components/forms/⚡contact-form/contact-form.php
# → resources/views/components/forms/⚡contact-form/contact-form.blade.php
```

Usage: `<livewire:blog::post-list />`, `<livewire:blog::forms.contact-form />`

### SFC — Single-File Component (`--sfc`)

Creates a single `.blade.php` file with an embedded PHP anonymous class. Ideal for simple components with minimal logic:

```bash
php artisan synapps:make-livewire blog PostList --sfc
# → resources/views/components/post-list.blade.php  (with embedded PHP class)
```

Usage: `<livewire:blog::post-list />`

### Package modules (`synmod-make`)

The standalone `synmod-make` binary inside package modules supports all the same flags:

```bash
./vendor/bin/synmod-make livewire ProductCatalog --mfc
./vendor/bin/synmod-make livewire Forms/ContactForm --mfc
./vendor/bin/synmod-make livewire QuickEdit --sfc
```

> **Note:** MFC and SFC components are auto-discovered by the `AutoRegistersComponents` trait via Livewire v4's `addNamespace()`. No manual component registration is required.

---

## Generated Structure

```
{module}/
├── config/              # Configuration files
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
│       ├── components/  # View components (Blade)
│       └── livewire/    # Livewire views
├── routes/
│   ├── web.php          # Frontend routes
│   ├── web.backend.php  # Backend routes (auto-auth)
│   ├── api.php              # API routes (unversioned, module-prefixed)
│   ├── api.override.php     # API override routes (no module prefix)
│   ├── api.v1.php           # API v1 routes (optional)
│   ├── api.v1.override.php  # API v1 override routes (no module prefix)
│   └── api.v2.php           # API v2 routes (optional)
├── src/
│   ├── Broadcasting/    # Broadcasting channels
│   ├── Casts/           # Custom Eloquent casts
│   ├── Console/
│   │   └── Commands/    # Artisan commands
│   ├── Contracts/       # Interfaces
│   ├── Enums/           # PHP enums
│   ├── Events/          # Event classes
│   ├── Http/
│   │   ├── Controllers/ # HTTP controllers
│   │   ├── Middleware/  # Middleware
│   │   ├── Requests/    # Form requests
│   │   └── Resources/   # API resources
│   ├── Jobs/
│   │   └── Middleware/  # Job middleware
│   ├── Listeners/       # Event listeners
│   ├── Livewire/        # Livewire components
│   ├── Mail/            # Mailable classes
│   ├── Models/
│   │   └── Scopes/      # Query scopes
│   ├── Notifications/   # Notifications
│   ├── Observers/       # Model observers
│   ├── Policies/        # Authorization policies
│   ├── Providers/       # Service providers
│   ├── Rules/           # Validation rules
│   ├── Traits/          # PHP traits
│   └── View/
│       └── Components/  # Blade component classes
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
Base:         SynApps\Modules\{ModuleName}
Models:       SynApps\Modules\{ModuleName}\Models
Controllers:  SynApps\Modules\{ModuleName}\Http\Controllers
Livewire:     SynApps\Modules\{ModuleName}\Livewire
Events:       SynApps\Modules\{ModuleName}\Events
Jobs:         SynApps\Modules\{ModuleName}\Jobs
Factories:    SynDB\Modules\{ModuleName}\Factories
Seeders:      SynDB\Modules\{ModuleName}\Seeders
Casts:        SynApps\Modules\{ModuleName}\Casts
Channels:     SynApps\Modules\{ModuleName}\Broadcasting
Commands:     SynApps\Modules\{ModuleName}\Console\Commands
Components:   SynApps\Modules\{ModuleName}\View\Components
Enums:        SynApps\Modules\{ModuleName}\Enums
Interfaces:   SynApps\Modules\{ModuleName}\Contracts
JobMiddleware: SynApps\Modules\{ModuleName}\Jobs\Middleware
Observers:    SynApps\Modules\{ModuleName}\Observers
Resources:    SynApps\Modules\{ModuleName}\Http\Resources
Rules:        SynApps\Modules\{ModuleName}\Rules
Scopes:       SynApps\Modules\{ModuleName}\Models\Scopes
Traits:       SynApps\Modules\{ModuleName}\Traits
```

### Package Modules

```
Base:         {Vendor}\{ModuleName}
Models:       {Vendor}\{ModuleName}\Models
Controllers:  {Vendor}\{ModuleName}\Http\Controllers
Livewire:     {Vendor}\{ModuleName}\Livewire
Events:       {Vendor}\{ModuleName}\Events
Jobs:         {Vendor}\{ModuleName}\Jobs
Factories:    {Vendor}\{ModuleName}\Factories
Seeders:      {Vendor}\{ModuleName}\Seeders
Casts:        {Vendor}\{ModuleName}\Casts
Channels:     {Vendor}\{ModuleName}\Broadcasting
Commands:     {Vendor}\{ModuleName}\Console\Commands
Components:   {Vendor}\{ModuleName}\View\Components
Enums:        {Vendor}\{ModuleName}\Enums
Interfaces:   {Vendor}\{ModuleName}\Contracts
JobMiddleware: {Vendor}\{ModuleName}\Jobs\Middleware
Observers:    {Vendor}\{ModuleName}\Observers
Resources:    {Vendor}\{ModuleName}\Http\Resources
Rules:        {Vendor}\{ModuleName}\Rules
Scopes:       {Vendor}\{ModuleName}\Models\Scopes
Traits:       {Vendor}\{ModuleName}\Traits
```

**Vendor Conversion:**
- `vm-engine/synmod-blog` → Vendor: `VmEngine`, Module: `Blog`
- `acme-corp/synmod-shop` → Vendor: `AcmeCorp`, Module: `Shop`

---

## Unified synmod-make Command

The `synmod-make` command is a **standalone bin executable** that provides a unified interface for generating any component type with an interactive menu.

> **⚠️ Important:** `synmod-make` is a **bin executable** (`./vendor/bin/synmod-make`), **NOT** an artisan command. Use `php artisan synapps:make-*` for artisan-based commands.

### Features

- 🎯 **Interactive Menu** - Shows all 30 component types to choose from
- 🚀 **Direct Usage** - Skip menu by specifying component type
- 📦 **Standalone Executable** - No Laravel required (works in packages)
- ⚡ **Fast & Intuitive** - Same options as individual commands
- 🔧 **Cross-Platform** - Works on Windows, Linux, and macOS

### Usage

**Interactive Mode (with menu):**
```bash
# Shows interactive menu to select component type
./vendor/bin/synmod-make

# Windows
vendor\bin\synmod-make
```

**Direct Mode (skip menu):**
```bash
# Format: synmod-make {type} {module} {name} [options]

# Examples
./vendor/bin/synmod-make enum todos TodoStatus
./vendor/bin/synmod-make trait todos HasUuid
./vendor/bin/synmod-make cast todos Json
./vendor/bin/synmod-make observer todos PostObserver --model=Post
./vendor/bin/synmod-make resource todos PostResource --collection
./vendor/bin/synmod-make component todos Alert
./vendor/bin/synmod-make interface todos Searchable
./vendor/bin/synmod-make scope todos ActiveScope
```

**Package Modules:**
```bash
# Using synmod-make with package modules
./vendor/bin/synmod-make enum example PriorityLevel --path=packages/synmod-example
./vendor/bin/synmod-make trait example Cacheable --path=packages/synmod-example
```

### Available Types

All 30 component types are supported:
- cast, channel, command, component, config, controller
- enum, event, factory, interface, job, job-middleware
- listener, livewire, mail, middleware, migration, model
- notification, observer, policy, provider, request, resource
- rule, scope, seeder, test, trait, view

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
php artisan synapps:make-channel orders OrderChannel
```

### Advanced Components

```bash
# Custom casts for model attributes
php artisan synapps:make-cast blog Money
php artisan synapps:make-cast blog Json

# Enums for typed constants
php artisan synapps:make-enum blog PostStatus
php artisan synapps:make-enum blog Priority

# Traits for reusable functionality
php artisan synapps:make-trait blog HasSlug
php artisan synapps:make-trait blog Publishable

# Interfaces for contracts
php artisan synapps:make-interface blog Searchable
php artisan synapps:make-interface blog Cacheable

# Observers for model events
php artisan synapps:make-observer blog PostObserver --model=Post
php artisan synapps:make-observer blog CommentObserver --model=Comment

# Query scopes
php artisan synapps:make-scope blog PublishedScope
php artisan synapps:make-scope blog ActiveScope

# Validation rules
php artisan synapps:make-rule blog ValidSlug
php artisan synapps:make-rule blog UniqueEmail
```

### API Resources

```bash
# Single resource
php artisan synapps:make-resource api PostResource
php artisan synapps:make-resource api UserResource

# Resource collections
php artisan synapps:make-resource api PostCollection --collection
php artisan synapps:make-resource api UserCollection --collection
```

### Blade Components

```bash
# View components (class + view)
php artisan synapps:make-component ui Alert
php artisan synapps:make-component ui Modal
php artisan synapps:make-component ui Card

# Inline components (class only)
php artisan synapps:make-component ui Badge --inline

# Standalone views
php artisan synapps:make-view blog posts/list
php artisan synapps:make-view blog partials/sidebar
```

### Console Commands

```bash
# Artisan commands
php artisan synapps:make-command blog PublishPosts --command=blog:publish
php artisan synapps:make-command blog CleanupOldPosts --command=blog:cleanup

# Job middleware
php artisan synapps:make-job-middleware queue RateLimitJobs
php artisan synapps:make-job-middleware queue SkipIfBatchCancelled
```

### Configuration Files

```bash
# Module configuration
php artisan synapps:make-config blog blog
php artisan synapps:make-config api api
```

### Using synmod-make for Quick Generation

```bash
# Interactive menu - select from all types
./vendor/bin/synmod-make

# Quick generation with type parameter
./vendor/bin/synmod-make enum todos TodoStatus
./vendor/bin/synmod-make trait users HasAvatar
./vendor/bin/synmod-make cast products Price
./vendor/bin/synmod-make observer posts PostObserver --model=Post
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
php artisan synapps:make-livewire blog PostList               # Standard (class-based)
php artisan synapps:make-livewire blog QuickPost --inline     # Inline (no view file)
php artisan synapps:make-livewire blog PostList --mfc         # Multi-File Component (MFC)
php artisan synapps:make-livewire blog PostList --sfc         # Single-File Component (SFC)
php artisan synapps:make-livewire blog Forms/ContactForm --mfc  # Nested MFC

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

# Components
php artisan synapps:make-component ui Button            # With view
php artisan synapps:make-component ui Icon --inline     # Inline (no view)

# Resources
php artisan synapps:make-resource api PostResource      # Single resource
php artisan synapps:make-resource api PostCollection --collection  # Collection

# Observers
php artisan synapps:make-observer blog PostObserver     # Generic observer
php artisan synapps:make-observer blog PostObserver --model="App\Models\Post"  # With model
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
