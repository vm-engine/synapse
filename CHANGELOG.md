# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
