# Synapse Modular Engine

A sophisticated modular architecture system for Laravel applications that enables both directory-based and package-based modules with automatic discovery, dependency management, and configuration generation.

## Features

- **Dual Module System**: Support for directory-based (`synapps/modules/`) and package-based (`vendor/*/synmod-*`) modules
- **Automatic Discovery**: Intelligent module and dependency detection
- **Configuration Management**: Builder pattern with lifecycle hooks, `env::` prefix support, and override system
- **Theme System**: Customizable themes with multiple layouts, CDN resources, and fallback support
- **Database Services**: DbConfig and DbLang for runtime configuration and multi-language support
- **Excel Import/Export**: Memory-efficient streaming import/export with background processing, progress tracking, and resume support
- **Blade Components**: Pre-built UI components for forms and common patterns
- **Livewire Traits**: WithDeleteToken, WithReturnUrl, and RemembersQueryParams for common Livewire patterns
- **Route Management**: Automatic route registration with module-specific prefixes
- **Service Container Integration**: Module-based bindings and service providers
- **API Request Logging**: Opt-in, redacted audit trail for API routes via the `api.log` middleware
- **Hardened CSP**: Config-driven Content-Security-Policy allowlist with per-request script nonces
- **Upgrade Audit**: Detects (and optionally auto-fixes) legacy patterns when upgrading between Synapse versions, via `synapps:upgrade-audit` or a standalone pre-upgrade script

## Quick Start

### Installation

```bash
# Add the private VCS repository to composer.json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "ssh://git@repo.4visionmedia.com:40288/vm-engine/synapse.git"
        }
    ],
    "require": {
        "vm-engine/synapse": "^2.4"
    }
}

# Install
composer update vm-engine/synapse

# Run interactive setup
php artisan synapps:setup
```

See complete [Installation Guide](docs/INSTALLATION.md).

### Basic Usage

```php
// Use database configuration
$maintenanceMode = dbconf('app.maintenance_mode', false);

// Get translations
echo ___('welcome.message');

// Use Blade components
<x-synapse-alert type="success" message="Welcome!" />

// Use services
use VmEngine\Synapse\Facades\DbConfig;
DbConfig::set('app.site_name', 'My Site');
```

## Documentation

### Getting Started

- **[Installation Guide](docs/INSTALLATION.md)** - Complete setup instructions
- **[Artisan Commands](docs/COMMANDS.md)** - All available commands and usage

### Core Features

- **[Theme System](docs/THEMES.md)** - Customizable themes with layouts, CDN resources (with SRI), and dark mode
- **[Database Services](docs/SERVICES.md)** - DbConfig, DbLang, Breadcrumbs, and API Request Logging services
- **[Excel Import/Export](docs/EXCEL.md)** - Streaming Excel/CSV import and export with background processing
- **[Blade Components](docs/COMPONENTS.md)** - UI components for forms, alerts, and more
- **[Helper Functions](docs/HELPERS.md)** - Global helpers for common tasks

### Module Development

- **[Creating Modules](docs/MODULES.md)** - Guide to building your own modules
- **[Module Generator](docs/MODULE-GENERATOR.md)** - Laravel-style make commands for generating module components
- **[Configuration](docs/CONFIGURATION.md)** - Advanced config features, env:: prefix, and overrides
- **[Traits](docs/TRAITS.md)** - WithDeleteToken, WithReturnUrl, RemembersQueryParams, and AutoRegistersComponents
- **[Testing Guide](TESTING.md)** - Comprehensive testing documentation
- **[IDE Helper](IDE_HELPER.md)** - Generate PHPDoc for better IDE support

### Technical Documentation

- **[CLAUDE.md](CLAUDE.md)** - Architecture, config generators, and internals
- **[Laravel Boost Integration](docs/LARAVEL_BOOST.md)** - Using Laravel Boost MCP tools with Synapse
- **[Changelog](CHANGELOG.md)** - Version history and changes
- **[Upgrade Guide](UPGRADE.md)** - Migration instructions between versions

## Requirements

- PHP 8.2+
- Laravel 12.x
- Livewire 4.x
- Composer
- Node.js & NPM

## Quick Reference

### Essential Commands

```bash
# Setup (run once)
php artisan synapps:setup

# Module discovery
php artisan synapps:discover

# Regenerate configs
php artisan synapps:config

# Generate module components
php artisan synapps:make-module blog --author="Your Name"
php artisan synapps:make-model blog Post -mfs
php artisan synapps:make-controller blog PostController --resource
php artisan synapps:make-livewire blog PostList

# Manage database config
php artisan synapse:db-config app.maintenance_mode true

# Manage translations
php artisan synapse:db-lang welcome.title "Welcome!" --language=en

# Create and manage themes
php artisan synapps:make-theme my-theme --copy-from=default

# Copy stub modules (home, todos) to try out
php artisan synapps:copy-stubs

# Publish settings module
php artisan synapps:publish-settings
```

### Helper Functions

```php
// Database configuration
$value = dbconf('app.maintenance_mode', false);
dbconf(['app.site_name' => 'My Site']);

// Translations
$text = ___('welcome.message');
$text = ___('menu.home', 'id'); // Specific language

// Paths
$path = synapps_path('modules/example');
$url = template_assets('css', 'style.css');

// Routing
<a href="/" class="{{ is_route('home') }}">Home</a>

// Themes
$theme = active_theme();
$logoUrl = theme_asset('images/logo.svg');
$cssPath = theme_path('css/theme.css');
```

### Blade Components

```blade
<!-- Alert -->
<x-synapse-alert type="success" message="Saved!" />

<!-- Form components -->
<x-synapse-checkboxes name="permissions" :options="$opts" :model="$selected" />
<x-synapse-datepicker />
<x-synapse-currency currency="$" />

<!-- File upload -->
<x-synapse-drag-n-drop wireModel="avatar" />          {{-- single image, with preview --}}
<x-synapse-file-drop wire-model="docs" multiple       {{-- multi-file, any type --}}
    :max-count="3" :allowed-types="['pdf','jpg']" />

<!-- UI components -->
<x-synapse-confirm-dialog title="Delete?" message="Are you sure?" />
<x-synapse-drawer title="Settings">...</x-synapse-drawer>
<x-synapse-toastr />
```

## Notifications

In-app notifications on top of Laravel's stock `notifications` table — no schema changes. Disabled by default; enable with `php artisan synotif:setup` (see [docs/COMMANDS.md](docs/COMMANDS.md#synotifsetup)).

**Sending:**

```php
use VmEngine\Synapse\Notifications\Notify;

Notify::make(new InvoicePaid($invoice))
    ->toUsers($user)                        // User model, user id, or an iterable mix of either
    ->toRoles('admin', except: [$userA])    // requires vm-engine/synapps-auth; string|array of slugs
    ->withToastr()                          // pop a toast when the recipient's bell picks it up
    ->queued()                              // resolve targets + send in a queued job, not inline
    ->send();
```

`send()` silently no-ops when notifications are disabled (config flag off, or the `notifications` table doesn't exist), optionally logging the attempt to `synapps.apps.notifications.log_channel`. An unknown role slug or a user id that doesn't resolve is logged as a warning and skipped — it never throws. `toRoles()` throws only when `vm-engine/synapps-auth` itself isn't installed, since that's a structural misconfiguration, not a data state.

**Data shape convention** — every notification class's `toArray()`/`toDatabase()` should return:

```php
public function toArray(object $notifiable): array
{
    return [
        'title' => 'notifications.invoice_paid.title',   // translation key, not a rendered string
        'message' => 'notifications.invoice_paid.message',
        'params' => ['invoice' => $this->invoice->number], // shared by title + message
        'icon' => 'fa-solid fa-file-invoice',
        'url' => route('invoices.show', $this->invoice),
        'toastr' => $this->toastr ?? false,               // set via HasToastrFlag + Notify::withToastr()
    ];
}
```

Title/message are rendered with `__($key, $params)` **at display time**, using the viewer's current locale — the same stored row renders correctly for every recipient regardless of language. To make `withToastr()` work, the notification class uses the provided trait:

```php
use VmEngine\Synapse\Notifications\Concerns\HasToastrFlag;

class InvoicePaid extends Notification
{
    use HasToastrFlag; // exposes $this->toastr, set by Notify::withToastr()
}
```

**UI:** embed once in your layout —

```blade
<livewire:synotif::bell />
<x-synapse::notification-panel />
```

`synotif::bell` renders the badge + dropdown (latest N, polling every 15s for new toastr-flagged notifications). `notification-panel` wraps the paginated "view all" list in `<x-synapse::modal>`; it's a **separate** embed rather than nested inside the bell because Livewire can't nest a namespaced child component (`synotif::list`) inside another Livewire component's own template.

**Config** (`synapps/config/apps.json` → `notifications` block, compiled to `config('synapps.apps.notifications.*')`):

```json
{
    "notifications": {
        "enabled": true,
        "log_channel": null,
        "dropdown_limit": 5,
        "modal_per_page": 15
    }
}
```

`enabled`/`log_channel` are set interactively by `synotif:setup` (or during `synapps:setup`/`synapps:reconfig`). `dropdown_limit`/`modal_per_page` are tuning knobs — edit `apps.json` directly and run `php artisan synapps:config` to change them.

## Support

For issues and questions, please contact the VM Engine development team or create an issue in the repository.

## License

This package is proprietary software developed by VM Engine.

---

**[📖 View Full Documentation](docs/)**
