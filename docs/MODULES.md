# Creating Modules

Synapse supports two types of modules: directory-based and package-based.

## Module Types

### Directory-based Modules

Located in `synapps/modules/{module-name}/`. Best for application-specific features.

**Advantages:**

-   Easy to create and modify
-   Part of your main application repository
-   No need for separate Composer package
-   Ideal for project-specific features

**Requirements:**

-   PSR-4 autoload configuration (handled by `synapps:discover`)
-   Module service provider
-   `module.json` configuration file

### Package-based Modules

Composer packages with `synmod-*`, `synmodule-*`, or `synapps-*` prefix.

**Advantages:**

-   Reusable across multiple projects
-   Version controlled separately
-   Can be published to Packagist or private repositories
-   Laravel package auto-discovery support

**Requirements:**

-   Standard Composer package structure
-   `module.json` in package root
-   Laravel service provider with auto-discovery

---

## Directory-based Module Structure

```
synapps/modules/{module-name}/
├── module.json              # Required: module configuration
├── src/
│   ├── {ModuleName}ServiceProvider.php
│   ├── Livewire/           # Livewire components
│   │   ├── UserList.php
│   │   └── UserForm.php
│   ├── Models/             # Eloquent models
│   │   └── User.php
│   └── Http/              # Controllers (if needed)
│       └── Controllers/
├── routes/
│   ├── web.php             # Standard module routes (URL & name prefixed)
│   ├── web.backend.php     # Backend routes (auto-auth, backend_prefix URL, 'backend.' name)
│   ├── web.override.php    # Override routes (no URL prefix, module name prefix)
│   └── api.php             # API routes
├── resources/
│   ├── views/
│   │   ├── components/     # Blade components
│   │   └── livewire/       # Livewire views
│   ├── js/                 # JavaScript files
│   ├── css/                # CSS files
│   └── assets/             # Static assets (images, fonts, etc.)
├── database/
│   ├── migrations/
│   ├── factories/
│   └── seeders/
└── tests/
    ├── Feature/
    └── Unit/
```

---

## module.json Schema

The `module.json` file is required for all modules and defines module metadata and configuration.

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
    "nav": {
        "backend": {
            "name": "__example::menu.parent",
            "icon": "fa-solid fa-example"
        }
    },
    "acl": {
        "feature": {
            "label": "__example::acl.feature",
            "create": 1,
            "read": 1,
            "update": 1,
            "delete": 1
        }
    },
    "dependencies": {
        "npm": {
            "main": {
                "axios": "^1.0.0"
            },
            "dev": {
                "@vitejs/plugin-vue": "^4.0.0"
            }
        },
        "composer": {
            "main": {
                "guzzlehttp/guzzle": "^7.0"
            },
            "dev": {
                "mockery/mockery": "^1.5"
            }
        }
    }
}
```

### Field Descriptions

| Field           | Type   | Required | Description                               |
| --------------- | ------ | -------- | ----------------------------------------- |
| `id`            | string | ✅       | Module identifier (snake_case)            |
| `id_case`       | object | ✅       | ID variants (snake, pascal, slug, studly) |
| `name`          | string | ✅       | Display name                              |
| `version`       | string | ✅       | Semantic version                          |
| `url_prefix`    | string | ✅       | URL prefix for routes (use "" for root)   |
| `description`   | string | ❌       | Module description                        |
| `author`        | string | ❌       | Author name                               |
| `email`         | string | ❌       | Author email                              |
| `load_priority` | int    | ❌       | Load order (lower = earlier, default: 99) |
| `nav`           | object | ❌       | Navigation configuration                  |
| `acl`           | object | ❌       | Access control configuration              |
| `dependencies`  | object | ❌       | npm and Composer dependencies             |

---

## Creating a New Directory-based Module

### Step 1: Create Directory Structure

```bash
mkdir -p synapps/modules/example/src
mkdir -p synapps/modules/example/routes
mkdir -p synapps/modules/example/resources/views
mkdir -p synapps/modules/example/database/migrations
```

### Step 2: Create module.json

Create `synapps/modules/example/module.json`:

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
    "version": "0.1.0",
    "url_prefix": "example",
    "load_priority": 99
}
```

**Important Notes:**

-   `id` must be unique across all modules
-   `id_case` must include all four variants (snake, pascal, slug, studly)
-   `url_prefix` is required (use `""` for root-level routes)
-   `version` should follow semantic versioning (MAJOR.MINOR.PATCH)

### Step 3: Create Service Provider

Create `synapps/modules/example/src/ExampleServiceProvider.php`:

```php
<?php

namespace SynApps\Modules\Example;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class ExampleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register bindings
    }

    public function boot(): void
    {
        // Register Livewire components
        Livewire::component('example::user-list', Livewire\UserList::class);
        Livewire::component('example::user-form', Livewire\UserForm::class);
    }
}
```

### Step 4: Run Discovery

Run the discovery command to register namespaces and dependencies:

```bash
php artisan synapps:discover
```

This command will:

-   Add PSR-4 autoload entries to `composer.json`
-   Register module namespaces
-   Merge module dependencies
-   Run `composer dump-autoload`

### Step 5: Create Routes

Create `synapps/modules/example/routes/web.php`:

```php
<?php

use Illuminate\Support\Facades\Route;

// These routes will be prefixed with /example
Route::get('/', function () {
    return view('example::index');
})->name('index');

Route::get('/about', function () {
    return view('example::about');
})->name('about');
```

### Step 6: Create Views

Create `synapps/modules/example/resources/views/index.blade.php`:

```blade
<x-layouts.app>
    <h1>Example Module</h1>
    <p>Welcome to the example module!</p>
</x-layouts.app>
```

---

## Route Types

Modules support four types of route files:

### 1. web.php - Standard Routes

```php
// URL: /example/users (with module's url_prefix)
// Route name: example.users.index
Route::get('/users', [UserController::class, 'index'])->name('users.index');
```

**Characteristics:**

-   URL prefixed with module's `url_prefix`
-   Route name prefixed with module ID
-   No automatic authentication

### 2. web.backend.php - Backend Routes

```php
// URL: /admin/users (with system backend_prefix)
// Route name: backend.users.index
// Automatically requires authentication
Route::get('/users', [UserController::class, 'index'])->name('users.index');
```

**Characteristics:**

-   URL prefixed with system `backend_prefix` (usually `/admin`)
-   Route name prefixed with `backend.`
-   Automatic authentication middleware
-   Ideal for admin panels

### 3. web.override.php - Override Routes

```php
// URL: /login (no URL prefix)
// Route name: example.login
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
```

**Characteristics:**

-   No URL prefix (root level)
-   Route name prefixed with module ID
-   Use for routes like login, register, homepage
-   Be careful of route conflicts

### 4. api.php - API Routes

```php
// URL: /api/users
// Route name: example.users.index
Route::get('/users', [ApiController::class, 'index'])->name('users.index');
```

**Characteristics:**

-   URL prefixed with `/api`
-   Route name prefixed with module ID
-   No automatic authentication (add manually if needed)

---

## Module Namespaces

Each directory-based module uses three namespaces that are automatically registered by `synapps:discover`:

### Application Namespace

```php
namespace SynApps\Modules\{ModuleName}\;

// Examples:
namespace SynApps\Modules\Example\;
namespace SynApps\Modules\Example\Livewire\;
namespace SynApps\Modules\Example\Models\;
```

### Seeders Namespace

```php
namespace SynDB\Modules\{ModuleName}\Seeders\;

// Example:
namespace SynDB\Modules\Example\Seeders\;

class ExampleSeeder extends Seeder
{
    //...
}
```

### Factories Namespace

```php
namespace SynDB\Modules\{ModuleName}\Factories\;

// Example:
namespace SynDB\Modules\Example\Factories\;

class UserFactory extends Factory
{
    //...
}
```

---

## Navigation Configuration

Add backend navigation in `module.json`:

```json
{
    "nav": {
        "backend": {
            "name": "__example::menu.users",
            "icon": "fa-solid fa-users",
            "route": "backend.users.index",
            "order": 10
        }
    }
}
```

The `__example::menu.users` syntax uses the translation system. Define translations in:

```php
// resources/lang/en/menu.php
return [
    'users' => 'User Management',
];
```

---

## Access Control (ACL)

Define permissions in `module.json`:

```json
{
    "acl": {
        "users": {
            "label": "__example::acl.users",
            "create": 1,
            "read": 1,
            "update": 1,
            "delete": 1
        },
        "roles": {
            "label": "__example::acl.roles",
            "create": 1,
            "read": 1,
            "update": 1,
            "delete": 0
        }
    }
}
```

Check permissions in code:

```php
if (Auth::user()->can('example.users.create')) {
    // User can create users
}
```

In Blade:

```blade
@can('example.users.create')
    <a href="{{ route('backend.users.create') }}">Create User</a>
@endcan
```

---

## Dependencies

Modules can declare their own npm and Composer dependencies in `module.json`:

```json
{
    "dependencies": {
        "npm": {
            "main": {
                "axios": "^1.0.0",
                "lodash": "^4.17.0"
            },
            "dev": {
                "@vitejs/plugin-vue": "^4.0.0"
            }
        },
        "composer": {
            "main": {
                "guzzlehttp/guzzle": "^7.0",
                "league/csv": "^9.0"
            },
            "dev": {
                "mockery/mockery": "^1.5"
            }
        }
    }
}
```

When you run `synapps:discover`, these dependencies are:

1. Aggregated from all modules
2. Smart-merged using semver (only upgrades)
3. Added to project's `package.json` and `composer.json`
4. Installed automatically

---

## Package-based Modules

### Creating a Package Module

1. **Create Composer package structure:**

```
vendor/your-org/synmod-example/
├── composer.json
├── module.json
├── src/
│   ├── ExampleServiceProvider.php
│   ├── Livewire/
│   └── Models/
├── routes/
│   └── web.php
└── resources/
    └── views/
```

2. **Configure composer.json:**

```json
{
    "name": "your-org/synmod-example",
    "type": "library",
    "require": {
        "php": "^8.2",
        "laravel/framework": "^12.0"
    },
    "autoload": {
        "psr-4": {
            "YourOrg\\SynmodExample\\": "src/"
        }
    },
    "extra": {
        "laravel": {
            "providers": ["YourOrg\\SynmodExample\\ExampleServiceProvider"]
        }
    }
}
```

3. **Create module.json** (same format as directory-based modules - all required fields must be present)

4. **Install via Composer:**

```bash
composer require your-org/synmod-example
```

The module will be automatically discovered and registered.

---

## Best Practices

### Module Organization

1. **Keep modules focused** - Each module should have a single responsibility
2. **Use namespaces consistently** - Follow the established namespace patterns
3. **Document dependencies** - Always declare dependencies in `module.json`
4. **Version carefully** - Use semantic versioning

### Code Structure

1. **Use Livewire** - Prefer Livewire components over traditional controllers
2. **Follow Laravel conventions** - Use Laravel's patterns and best practices
3. **Implement ACL** - Always define and check permissions
4. **Write tests** - Add unit and feature tests in the `tests/` directory

### Performance

1. **Lazy load modules** - Use `load_priority` to optimize load order
2. **Cache aggressively** - Use Laravel's caching for expensive operations
3. **Optimize assets** - Minimize and combine CSS/JS files

### Security

1. **Validate input** - Always validate user input
2. **Use ACL** - Implement proper permission checks
3. **Sanitize output** - Escape output to prevent XSS
4. **Protect routes** - Add authentication/authorization middleware

---

## Troubleshooting

### Module Not Loading

1. Check `module.json` syntax and ensure all required fields are present (`id`, `id_case`, `name`, `version`, `url_prefix`)
2. Run `php artisan synapps:discover`
3. Clear config cache: `php artisan config:clear`
4. Check service provider registration

### Routes Not Working

1. Verify route file exists and has correct name
2. Check `url_prefix` in `module.json`
3. Run `php artisan route:list` to see registered routes
4. Clear route cache: `php artisan route:clear`

### Views Not Found

1. Check view path matches namespace
2. Verify service provider loads views
3. Clear view cache: `php artisan view:clear`

### Autoload Issues

1. Run `composer dump-autoload`
2. Run `php artisan synapps:discover`
3. Check PSR-4 namespaces in `composer.json`
