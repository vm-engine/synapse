# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.3.5] - 2026-01-04

### Added

**Helper Functions:**
- Added `page_title()` helper function for managing page titles
  - Supports string and array inputs
  - Automatically appends app title from config
  - Customizable separator (default: '-')

**LTA CSS Framework:**
- Added button size variants: `.btn.xs`, `.btn.sm`, `.btn.lg`, `.btn.xl`
- Enhanced button component flexibility with predefined size utilities

### Fixed

**Code Quality:**
- Applied Laravel Pint formatting for PSR-12 compliance
- Fixed PHPDoc formatting and spacing issues in helper.php
- Removed unused imports in TodoCompletedChannel.php

## [1.3.3] - 2025-12-30

### Fixed

**Middleware Registration:**
- Fixed SecureHeaders middleware registration for Laravel 12 compatibility
- Replaced deprecated `$router->appendMiddleware()` with proper `$kernel->pushMiddleware()` method
- Middleware now registers correctly using `$this->app->booted()` callback with Kernel contract
- Added `Illuminate\Contracts\Http\Kernel` import for proper dependency injection

**Code Quality:**
- Applied Laravel Pint formatting to ensure PSR-12 compliance
- Fixed import ordering and spacing in SynapseServiceProvider

### Changed

**Laravel 12 Compatibility:**
- Updated middleware registration to use Laravel 12 recommended approach
- Service provider now uses Kernel contract for global middleware registration
- Improved compatibility with Laravel 12's new application structure

## [1.3.2] - 2025-12-30

### Fixed

**SecureHeaders Middleware:**
- Fixed SecureHeaders middleware namespace from `App\Http\Middleware` to `VmEngine\Synapse\Http\Middlewares`
- Corrected import statement in SynapseServiceProvider to match proper namespace
- Middleware now properly registers globally via `$router->appendMiddleware()`

**Code Quality:**
- Applied Laravel Pint code formatting across 130 files
- Fixed 4 style issues in SecureHeaders, SynapseServiceProvider, SynapseRouteServiceProvider, and helper.php
- Improved import ordering and spacing consistency

### Added

**Security Headers:**
- SecureHeaders middleware now applies comprehensive HTTP security headers:
  - Strict-Transport-Security (HSTS with preload)
  - X-Content-Type-Options (nosniff)
  - X-Frame-Options (SAMEORIGIN)
  - Referrer-Policy (strict-origin-when-cross-origin)
  - Permissions-Policy and Feature-Policy
  - Content-Security-Policy
  - X-XSS-Protection
  - X-Permitted-Cross-Domain-Policies
  - Expect-CT
- Auto-disabled in local environment for development convenience

## [1.3.1] - 2025-12-28

### Changed

**Refactoring:**
- Removed document management Blade components from Synapse core
  - Components were too specific to PM (Project Management) module's document structure
  - Moved `document-group-card.blade.php`, `document-revision-list.blade.php`, and `document-multiple-files-list.blade.php` to synmod-pm package
  - Removed component registrations from SynapseViewServiceProvider
  - Improves module encapsulation - each module can define its own document components

### Benefits

- **Better separation of concerns:** Core package no longer contains module-specific components
- **Improved maintainability:** Document components maintained within their respective modules
- **Module independence:** Each module can customize document components to match their specific requirements

## [1.3.0] - 2025-12-28

### Added

**AutoRegistersComponents Trait:**
- New `AutoRegistersComponents` trait for automatic component registration in module ServiceProviders
- Auto-discovers Livewire components from `src/Livewire/` directory
- Auto-discovers class-based Blade components from `src/View/Components/` directory
- Automatic prefix detection from module namespace (e.g., `VmEngine\Example` → `example`)
- Support for nested components with kebab-case naming
- Customizable via properties (`$componentPrefix`, `$autoRegisterLivewireComponents`, `$autoRegisterBladeComponents`)
- Selective registration methods: `registerComponents()`, `registerLivewireComponents()`, `registerBladeComponents()`
- Works for both directory-based and package-based modules
- Comprehensive test coverage (8 tests, all passing)

**Documentation:**
- Complete trait documentation in `docs/TRAITS.md`
- Usage examples and before/after comparisons
- Updated main `CLAUDE.md` with trait reference

**Code Quality:**
- Added PHPStan ignore rule for unused library traits
- Pint formatting applied to new trait code

### Benefits

- **Eliminates boilerplate:** No more manual `Livewire::component()` registration
- **Convention over configuration:** Components automatically registered with consistent naming
- **Reduces errors:** No typos in component names or missing registrations
- **Developer experience:** Just add trait and call `$this->registerComponents()`

## [1.2.0] - 2025-12-27

### Added

**Module Generator System:**
- 17 Laravel-style artisan commands for generating module components
  - `synapps:make-module` - Create complete module structure
  - `synapps:make-model` - Generate models with optional migration/factory/seeder
  - `synapps:make-controller` - Create controllers (plain/resource/API/invokable)
  - `synapps:make-migration` - Generate migrations (create/update table)
  - `synapps:make-factory` - Create model factories
  - `synapps:make-seeder` - Generate database seeders
  - `synapps:make-livewire` - Create Livewire components (standard/inline)
  - `synapps:make-request` - Generate form request validation
  - `synapps:make-middleware` - Create HTTP middleware
  - `synapps:make-event` - Generate event classes
  - `synapps:make-listener` - Create event listeners
  - `synapps:make-job` - Generate queued jobs
  - `synapps:make-provider` - Create service providers
  - `synapps:make-policy` - Generate authorization policies
  - `synapps:make-notification` - Create notifications
  - `synapps:make-mail` - Generate mailable classes
  - `synapps:make-test` - Create feature/unit tests

**Standalone Executable:**
- `bin/synmod-make` - Composer binary for package developers
- Works without Laravel installation (only requires Synapse package)
- Supports all 17 component generators
- Auto-detects module from composer.json
- Full Windows compatibility via Composer's automatic batch file creation

**Core Services:**
- `ModuleGeneratorService` - Main orchestrator for code generation
- `NamespaceResolver` - Handles complex 3-namespace structure (App/Factories/Seeders)
- `StubRenderer` - Template rendering engine with variable replacement

**Stub System:**
- 30+ template files covering all component types
- Support for multiple variants (resource/API/invokable controllers, create/update migrations, etc.)
- Smart namespace resolution for directory and package modules
- Automatic view name generation with kebab-case conversion

**Documentation:**
- Complete MODULE-GENERATOR.md documentation
- Quick reference guide included
- Updated README.md with module generator links
- Examples for both directory and package module workflows

### Fixed
- PHPStan level 5 compliance issues in module generator services
- Boolean operator precedence in controller stub detection
- Namespace resolution for package vendor names

### Changed
- All 17 commands tested and working for directory modules (`synapps/modules/`)
- All 16 component commands tested and working for package modules (standalone)
- Proper file paths for all generated components
- Correct namespace references between models, factories, and seeders
- Windows-compatible executable via Composer bin configuration

## [1.1.2] - 2025-12-20

### Fixed
- Configuration override system now properly deep merges with Laravel config files
- Added `deepMergeConfig()` method in `SynapseServiceProvider` for recursive array merging
- Changed `env('APP_KEY')` to `config('app.key')` in `SynAppsSetup` command for better Laravel practices

### Added
- PHPStan static analysis configuration (`phpstan.neon`) with Larastan integration at level 5
- PHPStan ignore rules for legitimate cases (config generation `env()` calls, component view-strings)
- PHPStan and Larastan as dev dependencies

### Changed
- Configuration override system now uses proper deep merge instead of `mergeConfigFrom()`
- All PHPStan level 5 checks pass successfully (213 tests passing)

## [1.1.1] - 2025-12-15

### Fixed
- Fixed typo in `Builder.php`: Changed `'apps' >` to `'apps' =>` for correct array syntax
- Fixed Windows path handling in `Modules.php` with `addcslashes()` to properly escape backslashes
- Fixed home module routing to have no URL prefix in `SynapseRouteServiceProvider.php`
- Added PHPDoc `@method create()` annotation to `Language` model for PHPStan compliance
- Added type hint for Language collection in `DbLang` service
- Removed unnecessary nullsafe operator in `DbLang::get()` method

### Changed
- Applied Laravel Pint code formatting fixes (unary operator spacing, braces positioning)
- All PHPStan level 5 checks now pass successfully

## [1.1.0] - 2025-12-06

### Added

**Database Services:**
- DbConfig service for runtime configuration storage with automatic caching
- DbLang service for multi-language translation system with auto-creation
- Database migrations for `configs` and `languages` tables
- Facades: `DbConfig` and `DbLang` with service container bindings
- Helper functions: `dbconf()` for config management and `___()` for translations
- Artisan commands: `synapse:db-config` and `synapse:db-lang`

**Security:**
- WithDeleteToken trait for secure delete operations with encrypted tokens
- Session-bound token validation to prevent unauthorized deletions
- Complete trait documentation in docs/TRAITS.md

**Configuration System:**
- Environment variable support in JSON config files using `env::` prefix
- Works in apps.json, system.json, modules.json, and module.json files
- Configuration override system in `synapps/config/override/`
- Automatic deep merging of override files with Laravel config files
- Complete documentation in docs/CONFIGURATION.md

**Commands:**
- `synapps:publish-settings` - Publish Settings module stub (requires SynAuth)
- `synapse:db-config` - Manage database configuration values
- `synapse:db-lang` - Manage database translations

**Documentation:**
- Created organized docs/ directory structure
- docs/INSTALLATION.md - Complete installation guide
- docs/SERVICES.md - Database services documentation
- docs/COMMANDS.md - All Artisan commands reference
- docs/COMPONENTS.md - Blade components documentation
- docs/HELPERS.md - Helper functions reference
- docs/MODULES.md - Module creation guide
- docs/CONFIGURATION.md - Advanced configuration features
- docs/TRAITS.md - WithDeleteToken trait guide
- Completely rewritten README.md with quick start guide
- Updated CLAUDE.md with all new features

### Changed
- Enhanced SynapseServiceProvider to register DbConfig and DbLang services
- Updated configuration builder to process `env::` prefixed values
- Improved service provider to apply configuration overrides automatically
- Reorganized documentation into focused, topic-specific files
- Updated README.md to be more concise with clear documentation links
- Enhanced CLAUDE.md with comprehensive technical reference

### Fixed
- Seeder file existence check to prevent errors with missing seeders
- Configuration override file discovery and merging logic

## [1.0.6] - 2025-11-29

### Added
- Auto-comment default Laravel user seeder in `synapps:patch-resources` command
- CHANGELOG.md file for tracking version history

### Changed
- Enhanced `synapps:patch-resources` to automatically clean up `database/seeders/DatabaseSeeder.php`
- Updated all documentation to use `synapps:config` instead of `config:cache`
- Clarified automatic `synapps:config` execution by `synapps:setup` and `synapps:discover`
- Improved setup workflow documentation in README.md and CLAUDE.md
- Removed redundant manual cleanup steps from setup guide

### Fixed
- Documentation consistency regarding configuration regeneration workflow

## [1.0.5] - 2024-XX-XX

Previous releases (see git tags for history)

## [1.0.4] - 2024-XX-XX

Previous releases (see git tags for history)

## [1.0.3] - 2024-XX-XX

Previous releases (see git tags for history)

## [1.0.2] - 2024-XX-XX

Previous releases (see git tags for history)

## [1.0.1] - 2024-XX-XX

Previous releases (see git tags for history)

## [1.0.0] - 2024-XX-XX

Initial stable release

[1.1.0]: https://repo.4visionmedia.com:4000/vm-engine/synapse/compare/v1.0.6...v1.1.0
[1.0.6]: https://repo.4visionmedia.com:4000/vm-engine/synapse/compare/v1.0.5...v1.0.6
[1.0.5]: https://repo.4visionmedia.com:4000/vm-engine/synapse/compare/v1.0.4...v1.0.5
[1.0.4]: https://repo.4visionmedia.com:4000/vm-engine/synapse/compare/v1.0.3...v1.0.4
[1.0.3]: https://repo.4visionmedia.com:4000/vm-engine/synapse/compare/v1.0.2...v1.0.3
[1.0.2]: https://repo.4visionmedia.com:4000/vm-engine/synapse/compare/v1.0.1...v1.0.2
[1.0.1]: https://repo.4visionmedia.com:4000/vm-engine/synapse/compare/v1.0.0...v1.0.1
[1.0.0]: https://repo.4visionmedia.com:4000/vm-engine/synapse/releases/tag/v1.0.0
