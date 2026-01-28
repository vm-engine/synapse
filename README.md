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
- **Security Traits**: WithDeleteToken for secure delete operations
- **Route Management**: Automatic route registration with module-specific prefixes
- **Service Container Integration**: Module-based bindings and service providers

## Quick Start

### Installation

```bash
# Add repository to composer.json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://repo.4visionmedia.com:4000/vm-engine/synapse.git"
        }
    ],
    "require": {
        "vm-engine/synapse": "^1.0"
    }
}

# Install
composer install

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

- **[Theme System](docs/THEMES.md)** - Customizable themes with layouts, CDN resources, and dark mode
- **[Database Services](docs/SERVICES.md)** - DbConfig, DbLang, and Breadcrumbs services
- **[Excel Import/Export](docs/EXCEL.md)** - Streaming Excel/CSV import and export with background processing
- **[Blade Components](docs/COMPONENTS.md)** - UI components for forms, alerts, and more
- **[Helper Functions](docs/HELPERS.md)** - Global helpers for common tasks

### Module Development

- **[Creating Modules](docs/MODULES.md)** - Guide to building your own modules
- **[Module Generator](docs/MODULE-GENERATOR.md)** - Laravel-style make commands for generating module components
- **[Configuration](docs/CONFIGURATION.md)** - Advanced config features, env:: prefix, and overrides
- **[Traits](docs/TRAITS.md)** - WithDeleteToken and other useful traits
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
- Livewire 3.x
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

<!-- UI components -->
<x-synapse-confirm-dialog title="Delete?" message="Are you sure?" />
<x-synapse-drawer title="Settings">...</x-synapse-drawer>
<x-synapse-toastr />
```

## Support

For issues and questions, please contact the VM Engine development team or create an issue in the repository.

## License

This package is proprietary software developed by VM Engine.

---

**[📖 View Full Documentation](docs/)**
