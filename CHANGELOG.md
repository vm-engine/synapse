# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.1] - 2025-11-15

### Fixed
- **SynAppsDiscover Command**: Added safety check to create `apps.dev.json` if missing, preventing errors during module discovery
- **SynAppsSetup Command**: Fixed vendor:publish tag from incorrect `synapps-config` to correct `synapse-config`

### Documentation
- Updated installation instructions to use stable `^1.0` version instead of `@dev`
- Fixed all Blade component examples to use correct hyphenated syntax (`x-synapse-*` instead of `x-synapse::*`)
- Updated all file paths from `packages/synapse` to `vendor/vm-engine/synapse` for accuracy
- Added critical warnings about `composer test` potentially refreshing main database
- Recommended using `./vendor/bin/pest` directly for safer test execution

## [1.0.0] - 2025-11-15

### Added
- **AdvSelect Component Enhancements**:
  - `allowCreate` prop - allows users to create new options by pressing Enter
  - Reset event listener for clearing selections externally
  - Visual feedback for creating new items with green highlight
  - Dark mode support for dropdown and create hint
- Example todos module in stubs for quick module scaffolding
- `.gitattributes` file to exclude development files from release archives
- `TestCase.php` for proper Orchestra Testbench integration in package tests

### Changed
- Improved AdvSelect component with better UX for tag/option creation
- Updated dropdown to show create hint when no results match search
- Enhanced dark mode styling across AdvSelect component
- Code quality improvements (imports ordering, formatting)
- Updated README.md with detailed setup guide showing automated command execution
- Updated CLAUDE.md to reflect `synapps:setup` automation improvements
- Improved test infrastructure for better package testing

### Fixed
- Fixed preload syntax in AdvSelect to use `@js()` directive for proper JSON encoding
- Fixed failing tests by implementing proper TestCase with Orchestra Testbench
- Removed incompatible `patch-tests` tests that require full Laravel context
- Code formatting in TestCase.php and SynAppsSetup.php

## [1.0.0-beta.1] - 2024-11-09

### Major Features

#### Modular Architecture System
- Dual module system supporting directory-based (`synapps/modules/`) and package-based (`vendor/*/synmod-*`) modules
- Automatic module discovery and registration
- PSR-4 namespace generation (3 per module: App, Seeders, Factories)
- Module-specific route files: `web.php`, `web.backend.php`, `web.override.php`, `api.php`
- Load priority system for controlling module initialization order

#### Configuration Management
- Builder pattern for configuration generation with 5-phase lifecycle (init, preLoop, loop, postLoop, finalize)
- Dual-generator pattern: `SynapseConfigGenerator` and `SynAppsConfigGenerator`
- Configuration builders: Apps, System, Modules, Binding, Auth, Nav
- Protected keys and smart merging for development overrides
- Compiled configuration caching with checksum validation

#### Artisan Commands
- `synapps:setup` - Interactive setup wizard with automated configuration
  - Collects project information
  - Copies stub directory
  - Generates `synapps/config/apps.json`
  - **Automatically runs**: `vendor:publish`, `synapps:link-errors`, `synapps:patch-resources`, `synapps:patch-tests`, `synapps:discover`
- `synapps:discover` - Module and dependency discovery
  - Scans for modules in multiple locations
  - Merges npm and composer dependencies using semver
  - Updates `composer.json` and `package.json`
  - Runs `composer dump-autoload` and `npm install` as needed
- `synapps:patch-resources` - Integrates Synapse JS/CSS into Laravel
  - Patches `resources/js/app.js` and `resources/css/app.css`
  - Idempotent (safe to run multiple times)
- `synapps:patch-tests` - Configures testing infrastructure
  - Patches `tests/Pest.php` with module test paths
  - Creates `.env.testing` with in-memory SQLite
  - Updates `composer.json` test script
  - Adds Browser, Modules, Packages testsuites to `phpunit.xml`
- `synapps:link-errors` - Creates symlink for custom error views

#### Blade Components

**Form Components**
- Checkboxes - Multiple checkbox inputs with array binding
- Radio Buttons - Radio button groups for single selection
- Select - Standard dropdown select
- Multi-Select - Multiple selection dropdown
- Password - Password field with show/hide toggle
- Currency - Currency input with formatting
- Datepicker - Date picker using Flatpickr
- Select2 - Advanced select with search (replaced by AdvSelect)
- AdvSelect - Modern alternative to Select2 with Livewire integration

**UI Components**
- Alert - Dismissible alerts with 4 types (success, warning, danger, info)
  - Auto-assigned FontAwesome icons
  - Alpine.js animations
  - Dark mode support
  - Border and solid style variants
- Confirm Dialog - Modal confirmation for destructive actions
- Drawer - Slide-out side panel
- Toastr - Toast notifications
- Modal - Modal dialogs with Livewire integration
- Toggler - Toggle switches
- File Upload - Drag-and-drop file upload component

#### Helper Functions

**Path Helpers**
- `synapps_path()` - Get absolute path to synapps directory
- `template_assets()` - Generate URL to template assets
- `DS` - Directory separator constant

**Routing Helpers**
- `is_route()` - Check if current route matches
- `is_route_prefix()` - Check route prefix
- `route_api_opt()` - API route options
- `route_web_opt()` - Web route options

**Number Helpers**
- `is_decimal()` - Check if value has decimals
- `clean_number_format()` - Format numbers without unnecessary decimals

**Array Helpers**
- `recuresive_array_merge()` - Deep merge arrays
- `array_is_list()` - Check if array is sequential

**Pagination Helpers**
- `pagination_format()` - Format pagination array
- `pagination_convert_link()` - Convert to offset/limit
- `pagination_generate()` - Generate LengthAwarePaginator

#### Services

**Breadcrumbs**
- Fluent API for building navigation breadcrumbs
- Methods: `make()`, `add()`, `hasItems()`, `items()`, `count()`
- Support for icons and complex navigation paths

**SynapseUtils**
- `listModuleNamespaces()` - Scan namespaces for modules
- `listModuleResources()` - Scan for specific resources
- `isSynAppsAutoloaded()` - Check autoload registration

**SyNav (Navigation Helper)**
- Menu configuration and generation
- Navigation structure management

### Testing

- **90 tests** with 141 assertions
- Comprehensive test coverage:
  - Helper functions: 195+ tests
  - Blade components: 70+ tests
  - Configuration generators: 51 tests
  - Services: 39 tests
  - Artisan commands: 9 tests
- PestPHP 3.x integration
- Orchestra Testbench for package testing
- In-memory SQLite for database tests
- Target coverage: ~85%

### Documentation

- **README.md** - Complete user guide with examples
- **CLAUDE.md** - Technical documentation for developers
- **TESTING.md** - Comprehensive testing guide
- **IDE_HELPER.md** - IDE helper generator documentation
- Interactive setup wizard with clear instructions

### Development Tools

- **IDE Helper Generator** (`generate-ide-helper.php`)
  - Generates PHPDoc for facades, models, and helper functions
  - Auto-scans multiple directories
  - Improves IDE autocompletion

### Service Providers

- `SynapseServiceProvider` - Main provider with module auto-registration
- `SynapseLangServiceProvider` - Language file loading
- `SynapseRouteServiceProvider` - Route registration with prefixes
- `SynapseViewServiceProvider` - Views and components

### Requirements

- PHP 8.2+
- Laravel 12.x
- Livewire 3.x
- Composer
- Node.js & NPM

### Dependencies

**Required**
- `laravel/framework` ^12.0
- `livewire/livewire` ^3.0
- `composer/semver` ^3.0

**Development**
- `pestphp/pest` ^3.0
- `pestphp/pest-plugin-laravel` ^3.0
- `orchestra/testbench` ^10.0
- `laradumps/laradumps` ^4.0
- `phpstan/phpstan` ^2.1
- `larastan/larastan` ^3.7

## [0.1.0] - Initial Development

### Added
- Initial package structure
- Basic modular architecture foundation
- Configuration system prototype
- First set of Blade components

---

## Version Naming Convention

This project follows [Semantic Versioning](https://semver.org/):

- **MAJOR** version (1.x.x) - Incompatible API changes
- **MINOR** version (x.1.x) - New features, backward compatible
- **PATCH** version (x.x.1) - Bug fixes, backward compatible

**Pre-release tags:**
- **alpha** - Early testing, unstable
- **beta** - Feature complete, testing phase
- **rc** - Release candidate, final testing

---

## Links

- [Repository](https://repo.4visionmedia.com:4000/vm-engine/synapse.git)
- [Issue Tracker](https://repo.4visionmedia.com:4000/vm-engine/synapse/issues)
- [Documentation](README.md)

[Unreleased]: https://repo.4visionmedia.com:4000/vm-engine/synapse/compare/v1.0.1...HEAD
[1.0.1]: https://repo.4visionmedia.com:4000/vm-engine/synapse/releases/tag/v1.0.1
[1.0.0]: https://repo.4visionmedia.com:4000/vm-engine/synapse/releases/tag/v1.0.0
[1.0.0-beta.1]: https://repo.4visionmedia.com:4000/vm-engine/synapse/releases/tag/v1.0.0-beta.1
[0.1.0]: https://repo.4visionmedia.com:4000/vm-engine/synapse/releases/tag/v0.1.0
