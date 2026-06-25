# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [2.4.3] - 2026-06-25

### Security
- **`WithReturnUrl::isSafeReturnUrl()`** — replaced `str_starts_with(url('/'))` with `parse_url()` host+scheme comparison to close an open-redirect bypass where `http://yourapp.com.attacker.com/` would pass the prefix check.
- **`WithDeleteToken::validateDeleteToken()`** — added guard before destructuring `explode('|', $decrypted, 2)` so a malformed decrypted string returns `false` instead of throwing an uncaught `ErrorException` (PHP `\Error`, not caught by the existing `\Exception` handler).
- **`helper.php / dbconf()`** — added `InvalidArgumentException` for keys that contain no dot separator, in both the getter and array-setter branches; previously produced an unhelpful `ErrorException: Undefined offset: 1`.
- **`SynAppsLinkErrors`** — Windows junction creation now uses `proc_open(['cmd', '/c', 'mklink', '/J', ...])` with an explicit argument list instead of string-interpolated `exec()`, preventing shell-injection on paths that contain special characters (spaces, parentheses, etc.).

### Fixed
- **`ProcessExcelImport`** — replaced stale read-modify-write (`$task->processed_rows + $processedInThisRun`) with `$task->increment('processed_rows', $rows->count(), [...])`. The old pattern produced wrong row counts on job retries and could cause rows to be silently skipped on resume.
- **`SynapseExcelCleanup`** — replaced `->get()` (loads all processing tasks into memory) with a DB-level staleness filter and `->cursor()->each()`. The staleness logic (`last_heartbeat_at`-or-`started_at`-based cutoff) is now evaluated in SQL, preventing memory exhaustion under high task volume.
- **`SynAppsDiscover::compareDependencies()`** — null-coalesced all four nested dependency keys (`npm.main`, `npm.dev`, `composer.main`, `composer.dev`); a `module.json` missing or partially omitting the `dependencies` key no longer aborts the entire discovery run with a `TypeError`.
- **`drag-n-drop.blade.php`** — Alpine `error` data property now initialises to `null` instead of the PHP `$error` prop value, preventing a stale server-side error from being shown before the user interacts with the component.

### Changed
- **`DbLang`** — added `reload()` method to flush the in-memory cache and re-load from the database; supports Octane workers and queue jobs that need to invalidate the language cache mid-process.
- **`SecureHeaders` CSP** — tightened `Content-Security-Policy` from `default-src *` (effectively no XSS protection) to `default-src 'self' data:` with explicit directives for `script-src`, `style-src`, `img-src`, `font-src`, `connect-src`, and `frame-src`. CDN resources loaded via `https:` remain permitted.
- **`SynapseServiceProvider`** — removed the hard `use Spatie\LaravelData\Data` import; optional-package detection now uses `class_exists('Spatie\\LaravelData\\Data')` string form, correctly expressing `spatie/laravel-data` as optional with no parse-time dependency.
- **Type hints** — added explicit parameter and return types to `DbConfig::get()`, `DbConfig::set()`, `DbLang::get()`, `DbLang::set()`, all `SyNav` methods, and `BuilderBase::loop()` parameter types. Added typed `$synapps` property to `BuilderBase`.

## [2.4.0] - 2026-05-23

### Added
- **Per-layout CSS/JS split** — Theme assets are now split into 4 CSS + 4 JS files per theme:
  - `theme-base.css` / `theme-base.js` — shared, loaded on every page
  - `theme-app.css` / `theme-app.js` — backend/admin layout only
  - `theme-frontend.css` / `theme-frontend.js` — public/frontend layout only
  - `theme-guest.css` / `theme-guest.js` — auth/guest layout only
- **8 Vite entry points** — `resources/css/{base,app,frontend,guest}.css` and `resources/js/{base,app,frontend,guest}.js`; `synapps:patch-resources` creates and wires all 8 automatically.
- **`@themeStyles` / `@themeScripts` now inject Vite assets** — blade layout files no longer need a separate `@vite()` call; the directives render CDN links + `base.{css,js}` + `{layout}.{css,js}` in one call.
- **`--split-layouts` flag for `synapps:patch-resources`** — targeted flag to create/patch only the per-layout split files (synapps intermediary CSS/JS, resource entry files, vite.config inputs, theme import lines).
- **Synapse intermediary split files** — `synapps/resources/css/synapps-{base,app,frontend,guest}.css` and matching `.js` files bridge the resource entry points to the active theme split files; created by `synapps:patch-resources`.
- **Theme-import line patching** — `synapps:patch-resources` updates the active-theme import lines in all 8 intermediary files whenever the active theme changes.

### Changed
- **`synapps:make-theme`** — scaffolds the 4-file CSS split and 4-file JS split structure instead of the former single `theme.css` / `theme.js`; next-steps output updated accordingly.
- **Default and Application theme stubs** — `theme.css` and `theme.js` replaced by 4 split files each; old single-file stubs deleted.
- **Sidebar arrow icon** — `.menu-item-arrow` gets `ml-auto` (flex push-right) so the chevron always appears flush-right in the nav item regardless of label length.
- **`datatable` CSS class** — restored full table padding, header/row styling, dividers, and hover states in `theme-app.css` (was dropped when `lta.css` was streamlined).
- **Dark mode text** — improved readability for body text, headings, and form labels in dark mode via overrides in `theme-base.css`.
- **Submenu styling** — corrected `menu-dropdown-item`, `menu-dropdown-item-active`, and `menu-dropdown-item-inactive` class definitions for proper indented submenu appearance.
- **Sidebar height** — changed `height: 100vh` to `height: auto; min-height: 100vh` so the sidebar grows with content instead of clipping on short viewports.

### Fixed
- **`patchPackageJson()`** — returned `true` (patched) when the watch script already had the correct value; now correctly returns `false` when no change is needed (idempotency fix).
- **`updateThemeJson()` in `synapps:make-theme`** — would fatal if `theme.json` contained invalid JSON; now guards against `null` from `json_decode()`.
- **`patchThemeImportLines()` regex** — pattern `theme-[a-z]+` did not match theme file names containing digits or hyphens after a letter; widened to `theme-[a-z][a-z0-9-]*`.

### ⚠️ Breaking Change
- **Themes must be split into 4 CSS + 4 JS files.** Any theme that still has a single `theme.css` / `theme.js` will fail `npm run build` because `synapps-base.css` now imports `theme-base.css` (the split file). Upgrade path:
  1. Split your `theme.css` → `theme-base.css`, `theme-app.css`, `theme-frontend.css`, `theme-guest.css`.
  2. Split your `theme.js` → `theme-base.js`, `theme-app.js`, `theme-frontend.js`, `theme-guest.js`.
  3. Remove direct `@import …theme.css` from `resources/css/app.css` and `import …theme.js` from `resources/js/app.js`.
  4. Run `php artisan synapps:patch-resources --split-layouts` to wire up the new files.
  5. Run `npm run build`.
  - Projects that want to stay on 2.3.x without upgrading should pin `"vm-engine/synapse": "~2.3"` in `composer.json`.

## [2.3.13] - 2026-05-18

### Changed
- **`<x-synapse-currency>`** — input now auto-selects its content on focus when the displayed value is `"0"`, so typing immediately replaces the zero rather than appending to it.

## [2.3.12] - 2026-05-04

### Fixed
- **Theme config compiler dropped custom layout buckets** — `Apps::loadActiveThemeConfig()` previously hardcoded the layout buckets it would read from `theme.json` to `['all', 'app', 'guest', 'frontend']`, silently discarding any custom layout key. Themes that define additional layouts (e.g. `landing`, `minisite`) for `css` / `js` / `headers` now have those buckets compiled into `config('synapps.apps.themes.*')` correctly. Backward compatible: existing themes with only the four standard buckets behave unchanged.

## [2.3.11] - 2026-04-28

### Added
- **`<x-synapse-file-drop>`** — new multi-file drop-zone Blade component, complements the existing `<x-synapse-drag-n-drop>` (which is single-image-only with preview). Use cases: forms with multiple file slots that accept any file type (PDF, docs, etc.), each with its own `maxCount` and per-slot allowed extensions.
  - **Class:** `VmEngine\Synapse\Components\FileDrop` (registered in `SynapseViewServiceProvider` as `synapse-file-drop`)
  - **View:** `resources/views/components/file-drop.blade.php` — self-contained scoped CSS via `@once`, drag-and-drop event chain, hidden input with `opacity:0` overlay, `is-dragging` state with brand-teal styling
  - **Props:** `wireModel`, `multiple`, `maxCount`, `maxSizeKb`, `allowedTypes` (extension array), `currentCount` (parent-supplied), `name`, `label`, `hint`, `fullText`, `uploadingText`, `error`, `module` (lang-namespace driver)
  - **Module-driven translations:** when `module="my-module"` is passed, the four default strings (`label`, `full`, `uploading`, `max`/`kb`) resolve via `__("my-module::file_drop.{key}")` with English fallbacks if any key is missing — lets each consumer module localise without per-instance prop overrides
  - **Existing files:** the component owns the drop zone only; consumers render their own existing-files list and feed the count via `currentCount` so the zone knows when the slot is full

### Changed
- **Documentation** — `docs/COMPONENTS.md` now has a dedicated **"File Upload Components"** section with a side-by-side comparison of `<x-synapse-drag-n-drop>` vs `<x-synapse-file-drop>`, full reference for both (the drag-n-drop component was previously undocumented), three usage examples for file-drop, and the per-module `lang/{locale}/file_drop.php` schema
- **README quick reference** — Blade Components snippet now includes a "File upload" group with one-line examples for both components

## [2.3.10] - 2026-04-27

### Changed
- **`<x-synapse-modal>` close behavior**: `close()` now dispatches a `closed-modal-{name}` event whenever the modal transitions from open to closed (close button, ESC key, or backdrop click), letting parent components react to all close paths. Includes a guard so the event only fires once per close.

### Fixed
- **`dbconf()` setter with array input**: When passed an associative array of `['group.key' => 'value']` pairs, the helper now iterates and persists every entry instead of silently writing only the first key.

## [2.3.9] - 2026-04-13

### Added
- **`WithSortablePagination` trait**: Reusable Livewire trait providing `sortBy()`, `$sortField`, `$sortDirection`, `$perPage` properties, `validatedSortField()` / `validatedSortDirection()` whitelist helpers, and `allowedSortFields()` override point for list components
- **`<x-synapse-sort-icon>` Blade component**: Renders a clickable sort icon (`fa-sort` / `fa-sort-up` / `fa-sort-down`) that calls `sortBy()` on the parent Livewire component; accepts `field`, `sort-field`, and `sort-direction` props
- **`<x-synapse-per-page-selector>` Blade component**: Renders a per-page `<select>` (10/15/25/50) bound to `wire:model.live="perPage"` alongside pagination links; accepts a `paginator` prop (`LengthAwarePaginator`)

## [2.3.8] - 2026-04-11

### Added
- **`registerConfigs()`**: `SynapseServiceProvider` now auto-loads PHP config files from `synapps/modules/{id}/config/*.php` for directory modules using `mergeConfigFrom()`, so modules can ship their own Laravel config files without manual registration

### Changed
- **Component prefix now snake_case**: `AutoRegistersComponents::getComponentPrefix()` switched from `Str::kebab()` to `Str::snake()` — multi-word module names now register with underscores (e.g. `my_module`) instead of dashes; single-word names are unaffected

### Fixed
- **Import cleanup**: `SynapseServiceProvider` — replaced FQCN references with proper `use` imports for `AliasLoader` and `Data`; removed redundant fully-qualified class names inline

## [2.3.7] - 2026-04-07

### Fixed
- **`synapps:make-migration --create`**: Migration stub `{{ table }}` placeholder was not replaced when using `--create` option; `array_merge($variables, $options)` was overwriting the computed `table` variable with `null` from the `--table` option; fixed by filtering null values from options before merging

## [2.3.6] - 2026-04-07

### Added
- **`resources/views/components` directory**: Module scaffolding now creates this directory automatically when generating a new module, enabling MFC component development out of the box
- **`--discover` flag on generator commands**: All `synapps:make-*` generator commands now accept a `--discover` flag to run module discovery after generation; removes the interactive prompt that was previously shown (non-interactive mode no longer auto-runs discovery)

### Changed
- **`ServiceProvider.stub`**: Generated service provider now includes `AutoRegistersComponents` trait and calls `$this->registerComponents()` in `register()`, so new modules auto-register Livewire and Blade components without extra setup
- **`glob` dependency**: Moved `glob` from main npm dependencies to dev-only and updated to `^13.0.0`; only `vite-plugin-static-copy` remains in main npm deps
- **Laravel 13 compatibility**: Updated `laravel/framework` requirement to `12.x|13.x`

### Fixed
- **`SynapseUtils::isSynAppsAutoloaded()`**: No longer throws `ErrorException` when `composer.json` does not exist at the base path (e.g., in Orchestra Testbench environments); returns `false` gracefully instead

## [2.3.4] - 2026-04-04

### Changed
- **Home stub module — MFC pattern**: Updated `stubs/synapps/modules/home/` to use Livewire MFC (Multi-File Component) pattern instead of a traditional controller
  - `HomeServiceProvider` now uses `AutoRegistersComponents` trait and calls `registerComponents()` for automatic component discovery
  - Route changed from `[HomeController::class, 'index']` to `Route::livewire('/', 'home::landing')`
  - Added new `landing` MFC component (`resources/views/components/landing/landing.php` and `landing.blade.php`) that resolves the active frontend theme layout with a fallback to `synapps::components.layouts.layout`

## [2.3.1] - 2026-03-02

### Added
- **`synapps:make-class`**: New generator command to create a plain PHP class inside a module (`src/` directory, namespace `SynApps\Modules\{Module}`); supports `--invokable` option to add an `__invoke(): void` method, matching Laravel's `make:class` behaviour
- **`synapps:make-exception`**: New generator command to create a custom exception class inside a module (`src/Exceptions/`, namespace `SynApps\Modules\{Module}\Exceptions`); supports `--render` and `--report` options (combinable) to scaffold the corresponding methods, matching Laravel's `make:exception` behaviour
- **Six new stubs**: `class/class`, `class/class.invokable`, `exception/exception`, `exception/exception.render`, `exception/exception.report`, `exception/exception.render-report`

### Changed
- **`synapps:make-livewire` — respects Livewire config default type**: When no type flag (`--mfc`, `--sfc`, `--class`, `--inline`) is provided, the command now reads `config('livewire.make_command.type')` to determine the default component type instead of always falling back to class-based; a new explicit `--class` flag forces class-based generation regardless of config
- **`synapps:patch-resources` — selective patching**: Command now accepts option flags (`--theme`, `--comment-default`, `--css-sources`, `--npm-watch`, `--js-back`, `--vite`) to run only specific patches instead of always running all patches; when no flag is provided all patches still run as before
- **`synapps:patch-resources` — refactored into methods**: Extracted each patch operation from the monolithic `handle()` into its own dedicated protected method (`patchAppJsImport()`, `patchAppCssImport()`); each method returns `bool` indicating whether a patch was applied
- **`bin/synmod-make`**: Changed file mode from executable (755) to non-executable (644)

## [2.3.0] - 2026-02-25

### Added

**Versioned and Override API Routes:**
- Modules can now define versioned API route files using the naming convention `routes/api.v{N}.php` (e.g., `api.v1.php`, `api.v2.php`)
- `SynapseRouteServiceProvider` automatically discovers all matching files via `glob('api.v[0-9]*.php')`
- Each versioned file is registered with:
  - URL prefix: `/api/{version}/{module_prefix}/` (e.g., `/api/v1/pm/`)
  - Route name prefix: `api.{version}.{module_id}.` (e.g., `api.v1.pm.`)
  - Middleware: `api`
- New `routes/api.override.php` — API override routes with no module URL prefix (registered at `/api/` root), mirrors `web.override.php` pattern
- New `routes/api.v{N}.override.php` — versioned API override routes with no module URL prefix (registered at `/api/{version}/`), one per version
- Multiple versions can coexist alongside the unversioned `api.php` — all are registered simultaneously
- No configuration required; file naming drives automatic discovery

---

## [2.2.2] - 2026-02-24

### Fixed

**SynapseFinder — MFC Resolution for "index" Directories:**
- Added `SynapseFinder` that overrides Livewire's `livewire.finder` singleton to fix an MFC component resolution bug
- Root cause: Livewire's `MultiFileParser` matches any directory whose name *contains* "index" as a substring (e.g. `todo-index`), causing incorrect resolution
- `SynapseFinder` restricts `MultiFileParser` to only directories whose name *ends with* "index" (i.e., the actual `todo-index/` directory name after emoji stripping)
- Registered in `SynapseServiceProvider::boot()` via `app()->singleton('livewire.finder', SynapseFinder::class)`

**Stubs Updates:**
- `stubs/synapps/modules/todos/routes/web.php`: route updated to use `::` namespace notation (`todos::todo-index`)
- `stubs/synapps/modules/todos/tests/Feature/TodoIndexTest.php`: added `RefreshDatabase` trait and updated component reference to `::` namespace notation

---

## [2.2.1] - 2026-02-20

### Fixed

**withBack() JS Helper — Stale URL After Livewire Pagination:**
- Fixed `withBack()` capturing a stale URL when used with Livewire pagination
- Root cause: Livewire updates `window.location.href` AFTER DOM morph, so Alpine's `:href` bindings evaluated `withBack()` before the URL contained the `page` parameter
- Added a `mousedown` event interceptor that re-computes the `back` query parameter with the current `window.location.href` at interaction time, before `wire:navigate` reads the href
- Also fixed `?` separator bug — now correctly uses `&` when the base URL already has query parameters

**Documentation Updates for v2.2.0:**
- Updated `docs/TRAITS.md` — AutoRegistersComponents section rewritten for Livewire v4 namespace system, updated directory structure, registration patterns (namespace notation), method table (`registerLivewireNamespace`), and notes
- Updated `CLAUDE.md` — AutoRegistersComponents features, example registrations, and methods updated for namespace notation
- Updated stub `todo-index.blade.php` — component references changed from dot notation to namespace notation (`todos::todo-form`, `todos::todo-list`)

## [2.2.0] - 2026-02-20

### Breaking Changes

**Livewire Component Namespace Notation:**
- Component tags change from dot notation (`example.todo-form`) to namespace notation (`example::todo-form`)
- This follows Livewire v4's standard `addNamespace()` system for package components
- All consuming modules using `AutoRegistersComponents` trait must update their Livewire component references

### Changed

**AutoRegistersComponents Trait — Livewire v4 Namespace System:**
- Replaced `registerMfcComponents()` (flat `DirectoryIterator` scan) and `registerLivewireComponents()` (recursive manual scan) with a single `registerLivewireNamespace()` method
- Now uses Livewire v4's native `Livewire::addNamespace()` for automatic component discovery
- Handles both class-based (`src/Livewire/`) and MFC/SFC (`resources/views/components/`) components in one call
- Nested MFC directories (e.g., `resources/views/components/forms/contact-form/`) are now discovered automatically — previously only flat directories were scanned
- Livewire handles emoji stripping, nested resolution, and class-based matching natively

**MFC Generation — Nested Path Support:**
- `synapps:make-livewire` command's `--mfc` flag now supports nested paths (e.g., `Forms/ContactForm`)
- Splits name by `/` or `\` into segments; last segment gets emoji prefix on directory, preceding segments become subdirectories
- Example: `php artisan synapps:make-livewire example Forms/ContactForm --mfc` → `resources/views/components/forms/⚡contact-form/`
- `bin/synmod-make` standalone binary updated with same nested MFC generation logic

### Migration Guide

1. Update component references from dot notation to namespace notation:
   - `<livewire:example.example-form />` → `<livewire:example::example-form />`
   - `<livewire:example.todo-form />` → `<livewire:example::todo-form />`
   - `<livewire:example.forms.contact-form />` → `<livewire:example::forms.contact-form />`
2. If using `registerMfcComponents()` or `registerLivewireComponents()` directly, replace with `registerLivewireNamespace()`
3. Run `composer update` and test all Livewire component references

## [2.1.1] - 2026-02-19

### Added

**RemembersQueryParams Livewire Trait:**
- New `RemembersQueryParams` trait for Livewire components that persists declared properties (search, page, filters, etc.) to the session
- Automatically restores list state when navigating back via breadcrumbs, browser back button, or direct links
- URL query params always take precedence over session values, so bookmarked/shared URLs work correctly
- Works alongside Livewire's `#[Url]` attribute
- Methods: `rememberedParams()`, `rememberKey()`, `forgetRememberedParams()`, `getRememberedParamValues()`
- 21 tests covering all methods, edge cases, and integration scenarios

## [2.0.1] - 2026-02-16

### Fixed

**Blade Facade Instance Divergence:**
- Replaced all `Blade::` facade calls with `app('blade.compiler')` direct calls in `SynapseViewServiceProvider` and `AutoRegistersComponents` trait
- Fixes component registration failures where the Blade facade root and the container-resolved BladeCompiler diverge (especially during testing with Livewire v4)
- `SynapseViewServiceProvider::boot()`: Changed `Blade::directive()` to `$blade->directive()` for theme directives (themeStyles, themeScripts, themeHeaders)
- `SynapseViewServiceProvider::registerSynapseComponents()`: Changed all 16 `Blade::component()` calls to `$blade->component()`
- `AutoRegistersComponents::registerBladeComponents()`: Changed `Blade::component()` to `app('blade.compiler')->component()`

## [2.0.0] - 2026-02-14

### Breaking Changes

**Livewire v4 Upgrade:**
- Livewire dependency changed from `3.x` to `^4.0` — all consuming packages must upgrade to Livewire v4
- Stub components converted from class-based (`src/Livewire/`) to MFC format (`resources/views/components/`)
- Stub routes migrated from `Route::get(Component::class)` to `Route::livewire(Component::class)`
- JavaScript hooks migrated from Livewire v3 API to v4 interceptor pattern

### Added

**Livewire v4 MFC/SFC Component Generation:**
- New `--mfc` flag for `synapps:make-livewire` command to generate Multi-File Components
  - Creates directory structure: `resources/views/components/{name}/{name}.php` + `{name}.blade.php`
  - Uses anonymous class format (`new class extends Component {}`)
- New `--sfc` flag for `synapps:make-livewire` command to generate Single-File Components
  - Creates single `.blade.php` file with embedded PHP anonymous class
- Three new stub templates:
  - `stubs/module-generator/livewire/component.mfc.stub` — MFC PHP class
  - `stubs/module-generator/livewire/component.mfc.blade.stub` — MFC Blade template
  - `stubs/module-generator/livewire/component.sfc.stub` — SFC combined file
- Updated `bin/synmod-make` standalone executable with `--sfc` and `--mfc` support for package modules

**AutoRegistersComponents Trait:**
- Added `registerMfcComponents()` method for automatic MFC component discovery
- Scans `resources/views/components/` for directories containing matching `.php` + `.blade.php` pairs
- MFC components registered alongside traditional class-based components

### Changed

**Livewire v4 JS Hook Migration:**
- `stubs/.../loader.blade.php`: Replaced `Livewire.hook('commit')`, `morph.updated`, `message.processed` with `Livewire.interceptMessage()` using `onSuccess`/`onError`/`onFailure` callbacks
- `resources/views/components/toggler.blade.php`: Replaced `Livewire.hook('morph.updated')` with `Livewire.interceptMessage()`
- `resources/views/components/adv-select.blade.php`: Replaced `Livewire.hook('message.processed')` with `Livewire.interceptMessage()`

**Stub Component Conversion (5 components to MFC):**
- Settings module: `ConfigurationList`, `LanguagesList` — moved from `src/Livewire/` + `resources/views/livewire/` to `resources/views/components/{name}/`
- Todos module: `TodoForm`, `TodoIndex`, `TodoList` — same MFC conversion
- Stub service providers updated to use `AutoRegistersComponents` trait instead of manual registration

**Route Migration:**
- `stubs/modules/settings/routes/web.backend.php`: `Route::get()` → `Route::livewire()` for 2 routes
- `stubs/synapps/modules/todos/routes/web.php`: `Route::get()` → `Route::livewire()` for 1 route

### Fixed

- PHPStan errors in `SynmodMakeCommand.php`: Replaced null values with proper `InputArgument`/`InputOption` constants
- `ModuleGeneratorService::$stubRenderer` visibility changed to public for MFC generation access

### Migration Guide

1. Update your `composer.json` to require `"livewire/livewire": "^4.0"`
2. Replace any `Livewire.hook('commit')`, `morph.updated`, or `message.processed` JS hooks with `Livewire.interceptMessage()` pattern
3. Replace `Route::get('/path', Component::class)` with `Route::livewire('/path', Component::class)` in route files
4. Optionally convert class-based components to MFC format using `--mfc` flag
5. Run `composer update` to install Livewire v4

## [1.7.3] - 2026-02-11

### Fixed

**Config Override System:**
- Apply PHP's `date_default_timezone_set()` when `app.timezone` is overridden via `synapps/config/override/app.php`, ensuring PHP's internal timezone matches Laravel's config

## [1.7.2] - 2026-02-09

### Fixed

**WithDeleteToken Trait:**
- Renamed `booted()` to `bootWithDeleteToken()` to follow Laravel's trait boot convention, preventing conflicts when multiple traits or the model itself define `booted()`
- Added `initializeWithDeleteToken()` to automatically hide `delete_token` from model serialization (JSON/array), preventing token leakage in API responses

## [1.7.1] - 2026-01-31

### Added

**Export Reference Tracking:**
- Nullable indexed `reference` column on both `excel_exports` and `excel_imports` tables
- `reference()` builder method on `ExcelExporter` and `ExcelImporter` for setting a tracking identifier
- `findByReference()` static method on `ExcelExport` and `ExcelImport` models to look up tasks by reference
- `scopeForReference()` query scope on both models for filtering by reference
- Migration `2026_01_31_000001_add_reference_to_excel_tables.php`

**Dot Notation for Export Columns:**
- Support dotted paths (e.g., `'client.company.name'`) in column definitions for resolving nested relationships
- Resolved via `data_get()` — works with Eloquent relationships and nested objects
- Works in both sync and background exports (plain strings are serializable)
- Added `resolveStringValue()` helper in `ExcelExporter` and `ProcessExcelExport`

**Column Transformers:**
- `ColumnTransformer` contract (`src/Services/Excel/Contracts/ColumnTransformer.php`) for serializable value formatters
- Array-type column definitions: `['field' => 'attr', 'transformer' => Class::class, 'config' => [...]]`
- Five built-in transformers:
  - `DateFormatTransformer` — format dates (default: `Y-m-d`)
  - `NumberFormatTransformer` — format numbers with decimals and separators
  - `BooleanTransformer` — convert booleans to labels (default: Yes/No)
  - `DefaultValueTransformer` — replace null/empty with a default string
  - `UpperCaseTransformer` — uppercase string values
- Transformer arrays are JSON-serializable and work in background export jobs
- `applyTransformer()` helper in `ExcelExporter` and `ProcessExcelExport`

**Tests:**
- `ColumnTransformerTest` — 26 unit tests for all 5 built-in transformers
- 6 new tests in `ExcelExporterTest` for dot notation, transformers, and serialization
- 8 new tests in `ExcelExportTest` for reference model features (findByReference, scopeForReference)
- 4 new tests in `ExcelJobTest` for background reference dispatch and transformer integration
- Total: 374 tests, 744 assertions (up from 330 tests)

**Documentation:**
- Updated `docs/EXCEL.md` with Reference Tracking, Dot Notation, and Column Transformers sections
- Updated `docs/SERVICES.md` with reference, dot notation, and transformer examples
- Updated `CLAUDE.md` with new Excel features and contracts

### Changed

- PHPStan ignore list in `phpstan.neon` updated for Excel-related enum comparison and runtime guard patterns
- Added `reportUnmatchedIgnoredErrors: false` to `phpstan.neon`

## [1.7.0] - 2026-01-29

### Added

**Excel Import/Export Utility Classes:**
- New memory-efficient Excel import/export system with streaming support
  - `ExcelImporter` builder API for CSV/XLSX file imports
  - `ExcelExporter` builder API for CSV/XLSX file exports
  - Constant-memory streaming via OpenSpout ^4.0
  - Template-based exports via PhpSpreadsheet ^2.0 (preserves formatting, formulas, charts)
  - Background processing via Laravel queued jobs with progress tracking
  - Resume capability for failed/stale imports and exports
  - Heartbeat-based stale task detection

**Import Features:**
- Sync import returning all rows as a Collection
- Three column mapping strategies: sequential array, associative array (explicit index), and closure
- Chunked import for memory-efficient processing of large files
- Background import via `dispatch()` with `ProcessesImport` contract
- Resume from `failed_at_row` for imports with side effects
- Automatic file type detection (CSV, XLSX)

**Export Features:**
- Sync export to CSV/XLSX with simple or associative column definitions
- Closure columns for computed values (e.g., `fn($user) => $user->department?->name`)
- Custom sheet names and starting rows
- Template-based export preserving original formatting
- Download response helper for browser downloads
- Background export via `dispatch()` with `ExportQuery` contract
- Idempotent resume (re-runs from scratch)

**Column Styling (XLSX only):**
- `columnStyles()` method for per-column styling (width, font, alignment, numberFormat, borders, fill)
- `headerStyle()` method for uniform header row styling
- `StyledWriter` using PhpSpreadsheet for full styling support
- Automatic header row freeze pane when styles are applied
- `autoSize` support for automatic column width calculation
- Styles fully serializable for background export jobs
- Combine styles with template exports

**Background Export Filters:**
- `withFilters()` method to pass user-defined filters to background exports
- Filters passed to `ExportQuery` constructor via Laravel's container
- Enables dynamic queries based on user input (year, status, department, etc.)

**Background Processing:**
- `ProcessExcelImport` queued job with `$tries = 3`, `$timeout = 3600`, `$backoff = [30, 60, 120]`
- `ProcessExcelExport` queued job with same retry configuration
- Heartbeat tracking via `last_heartbeat_at` column for stale detection
- Four events: `ExcelImportCompleted`, `ExcelImportFailed`, `ExcelExportCompleted`, `ExcelExportFailed`
- `ExcelTaskStatus` enum: Pending, Processing, Completed, Failed, Cancelled

**Database:**
- `excel_imports` migration with status, progress tracking, heartbeat, and resume support
- `excel_exports` migration with status, progress tracking, and heartbeat support
- `ExcelImport` and `ExcelExport` Eloquent models with stale detection methods

**Contracts:**
- `ReaderInterface` for pluggable file readers
- `WriterInterface` for pluggable file writers
- `ProcessesImport` contract for background import chunk processing
- `ExportQuery` contract for serializable export queries (supports constructor filters)

**Support Classes:**
- `ColumnMapper` with sequential, associative, and closure mapping plus serialization
- `ChunkIterator` for memory-efficient chunked row processing
- `SpoutReader` and `SpoutWriter` for OpenSpout streaming
- `TemplateWriter` for PhpSpreadsheet template-based exports
- `StyledWriter` for PhpSpreadsheet styled exports with full formatting

**Commands:**
- `synapse:excel-cleanup` command to mark stale processing tasks as failed
  - Configurable timeout via `--timeout` option (default: 3900 seconds)
  - Schedulable via `Schedule::command('synapse:excel-cleanup')->everyFiveMinutes()`

**Documentation:**
- Complete API documentation in `docs/EXCEL.md` including Column Styling section
- Updated `README.md` with Excel feature references
- Updated `CLAUDE.md` with command reference and service documentation
- Updated `docs/SERVICES.md` with Excel Import/Export service section and styling examples
- Updated `docs/COMMANDS.md` with cleanup command reference

**Tests:**
- 92 Excel-related tests (329 total, 661 assertions)
  - `ColumnMapperTest` - 13 tests for all mapping strategies and serialization
  - `ChunkIteratorTest` - 6 tests for chunked iteration and skip rows
  - `ExcelImporterTest` - 8 tests for sync CSV/XLSX import, chunked import, all mapping types
  - `ExcelExporterTest` - 14 tests for CSV/XLSX export, template export, closure columns, styling
  - `StyledWriterTest` - 14 tests for column widths, autoSize, header styles, data styles, freeze pane
  - `ExcelImportTest` - 11 feature tests for model CRUD, stale detection, status casting
  - `ExcelExportTest` - 10 feature tests for model CRUD, stale detection, resume validation
  - `ExcelJobTest` - 16 feature tests for dispatch, resume, stale resume, cleanup, styles, filters

**Dependencies:**
- Added `openspout/openspout: ^4.0` to require (streaming CSV/XLSX)
- Added `phpoffice/phpspreadsheet: ^2.0` to require (template-based and styled exports)

### Benefits

- **Memory Efficient:** Streaming imports/exports with constant memory usage via OpenSpout
- **Background Processing:** Large files processed via queued jobs with progress tracking
- **Resilient:** Auto-retry with backoff, heartbeat monitoring, and resume from failure point
- **Flexible API:** Builder pattern with multiple column mapping strategies
- **Template Support:** Export to pre-formatted Excel templates preserving styling
- **Professional Styling:** Column widths, header formatting, number formats, freeze panes
- **Dynamic Filters:** Pass user input filters to background export queries
- **Well Tested:** 92 comprehensive Excel tests covering unit, feature, and job scenarios

## [1.6.0] - 2026-01-23

### Added

**Password Generator Component:**
- New `<x-synapse::password-generator>` Blade component for secure password generation
  - Read-only text field that displays generated passwords in plain text
  - Enforces strong password generation (prevents weak user-created passwords)
  - Three action buttons: Generate (refresh), Apply (checkmark), Copy (clipboard)
  - Separate generate and apply workflow for user control
  - Supports setting single or multiple Livewire properties simultaneously
  - Configurable password length (minimum: 8 characters, default: 12)
  - Generates passwords with guaranteed character variety (lowercase, uppercase, numbers, symbols)
  - Alpine.js powered with no wire:model dependency
  - Dispatches `password-set` and `password-copied` custom events

**Component Features:**
- Password field is always visible as plain text (type="text")
- Read-only enforcement prevents manual password entry
- Generate button uses FontAwesome icon (`fa-solid fa-refresh`)
- Apply button only shown when `model` prop is provided
- Users can generate multiple passwords before applying
- Copy to clipboard functionality with browser Clipboard API
- Full dark mode support with Tailwind CSS

**PHP Component Class:**
- `VmEngine\Synapse\Components\PasswordGenerator` class
- Accepts `model` (string|array|null) for Livewire property binding
- Accepts `length` (int) with automatic minimum enforcement
- Registered as `synapse-password-generator` in ViewServiceProvider

**Documentation:**
- Comprehensive usage guide: `docs/COMPONENTS_PASSWORD_GENERATOR_EXAMPLE.md`
- Detailed workflow documentation: `docs/COMPONENTS_PASSWORD_GENERATOR_WORKFLOW.md`
- Migration guide and changes overview: `docs/COMPONENTS_PASSWORD_GENERATOR_CHANGES.md`
- Updated `docs/COMPONENTS.md` with component reference
- Complete example form: `examples/password-generator-form.blade.php`

**Tests:**
- 11 comprehensive unit tests for PasswordGenerator component
- Tests cover instantiation, property storage, length enforcement, and render method
- All tests passing (239 total tests in suite)

### Benefits

- **Enhanced Security:** Enforces strong, generated passwords across the application
- **Better UX:** Clear workflow - generate, review (always visible), then apply
- **Flexible Binding:** Set password and confirmation fields simultaneously with array model
- **Developer Friendly:** Well-documented with examples and workflow guides
- **Type Safe:** Full type hints and PHPStan level 5 compliant

## [1.5.6] - 2026-01-20

### Added

**Composer Patch Commands:**
- New `synapse:patch-composer` command for local development workflow
  - Merges `composer.local.json` into `composer.json` for path repository development
  - Automatically backs up original to `composer.original.json`
  - Deep merges configuration arrays (preserves existing values)
  - Idempotent `.gitignore` updates to exclude patch files
  - Safe to run multiple times with confirmation prompts
- New `synapse:restore-composer` command to undo patches
  - Restores original `composer.json` from backup
  - Removes backup file after restoration
  - Confirmation prompt before destructive operations
- Added example `stubs/composer.local.json.example` for reference
- Comprehensive test suite with 6 tests covering all scenarios

**Documentation:**
- Updated `CLAUDE.md` with detailed command documentation
  - Added commands to quick reference table
  - Included usage examples and best practices
  - Documented deep merge behavior and idempotency

### Benefits

- **Better Local Development:** Seamlessly work with path repositories without modifying committed `composer.json`
- **Git-Friendly:** Patch files automatically excluded from version control
- **Safe Operations:** Backup system ensures original configuration is never lost
- **Team-Friendly:** Each developer can maintain their own `composer.local.json` without conflicts
- **Well-Tested:** Full test coverage ensures reliability

## [1.5.5] - 2026-01-13

### Added

**Theme System:**
- Badge utility classes in Application theme CSS
  - Added `.badge` base class with gray styling
  - Added size variants: `.badge.sm`, `.badge.xl`
  - Added color variants: `.badge.info`, `.badge.success`, `.badge.danger`, `.badge.warning`, `.badge.primary`, `.badge.secondary`
  - Consistent styling with rounded corners, padding, and font-medium weight

### Changed

**Code Quality:**
- Applied Laravel Pint formatting across theme files and components
  - Updated Tailwind CSS class ordering to follow Pint conventions
  - Changed deprecated `flex-shrink-0` to modern `shrink-0` utility class
  - Improved code consistency across all Blade components and theme layouts

**Blade Components:**
- Reformatted Alert component with proper Tailwind class ordering
- Applied consistent class ordering in theme layouts (app, guest, header-nav, sidebar-menu)

**Theme Stubs:**
- Modernized class names in Application theme layouts
- Updated Default theme CSS with current Tailwind utilities
- Consistent use of modern flexbox utilities throughout theme files

### Benefits

- **Better Styling Options:** New badge classes provide consistent visual indicators
- **Modern CSS:** Using current Tailwind v4 utility classes (`shrink-0` vs `flex-shrink-0`)
- **Code Consistency:** All files follow Laravel Pint formatting standards
- **Maintainability:** Proper class ordering makes templates easier to read and maintain

## [1.5.4] - 2026-01-13

### Fixed

**Blade Components:**
- Enhanced `<x-synapse::adv-select>` component value comparison logic
  - Changed strict equality (`===`) to loose equality (`==`) for value field comparisons
  - Fixes issues where numeric IDs stored as strings fail to match in static options
  - Improved null/undefined check: `!currentValue` → `currentValue === null || currentValue === undefined`
  - Fixed `syncWithWire()` to properly handle empty selection: `ids[0] || null` → `ids.length > 0 ? ids[0] : null`
  - Applied consistent code formatting and spacing

### Benefits

**Type Safety:**
- Component now correctly handles type mismatches between numeric and string IDs
- More robust value matching across different data sources (static options, AJAX responses)
- Prevents false negatives when comparing database integers with form string values

**Reliability:**
- Empty selections no longer incorrectly sync `undefined` to Livewire
- More explicit null/undefined checks prevent edge case bugs

## [1.5.3] - 2026-01-13

### Added

**Blade Components:**
- Enhanced `<x-synapse::adv-select>` component with automatic value loading
  - Added `loadMethod` prop for loading initial values by ID(s) via AJAX
  - Added `autoLoad` prop (default: `true`) to enable/disable automatic loading
  - Component now automatically reads values from `wireModel` and displays correct labels
  - Smart fallback chain: static options → loadMethod → value as label
  - Supports both single and multiple selection modes
  - No longer requires manual `preload` prop conversion in most cases
  - Example: Component with `wire-model="user_id"` automatically loads user #5's label on init

### Changed

**Blade Components:**
- `<x-synapse::adv-select>` initialization now async to support AJAX loading
- Updated component documentation with auto-load examples and migration guide
- Legacy `preload` prop still supported for backward compatibility

**Examples:**
- Simplified todo form example to use auto-loading instead of manual preload conversion
- Removed boilerplate `@php` block for tag preload conversion

### Benefits

**Developer Experience:**
- Eliminates boilerplate code for converting values to preload format
- Simplifies edit forms - just pass the ID, component handles the rest
- Works seamlessly with both static and AJAX data sources
- Reduces code duplication across forms

## [1.5.2] - 2026-01-11

### Fixed

**Blade Components:**
- Enhanced confirm-dialog component with loading state and event handling
  - Added loading spinner during confirmation action
  - Added `x-on:synapse-confirmed.window` event listener for external close trigger
  - Commented out automatic close after confirm to allow wire:loading states
  - Fixed confirm button to show loading state during async operations
- Improved modal component overflow handling
  - Fixed modal container height to use `max-h-[calc(100vh-2rem)]` for better viewport handling
  - Added flexbox layout with `flex flex-col` for proper content distribution
  - Removed redundant `max-h-11/12` class that caused overflow issues
- Enhanced drag-n-drop component structure
  - Added `x-on:dragndrop-clear-input.window` event for programmatic clearing
  - Improved Alpine.js initialization formatting for better readability
  - Applied Pint formatting for consistent code style

**Vite Configuration:**
- Significantly improved `synapps:patch-resources` command for Vite config patching
  - Added watch mode optimization - static asset copying now disabled in `--watch` mode for faster HMR
  - Converts object-based config to function-based config automatically for watch detection
  - Wraps viteStaticCopy in conditional `...(!isWatch ? [...] : [])` to skip in watch mode
  - Improves development experience with faster hot reloads
  - Production builds still copy all static assets correctly
  - Added comprehensive comments explaining watch mode behavior
  - Enhanced theme assets comment to clarify compiled vs static assets

**Module Generator:**
- Fixed Livewire component stub view path syntax
  - Changed from `{{ moduleSlug }}::livewire.{{ view }}` to `{{ moduleSlug }}.livewire.{{ view }}`
  - Ensures consistent view path format across generated components

**Code Quality:**
- Fixed PHPStan level 5 analysis error in SynAppsPatchResources
  - Replaced `strpos() !== false` with `str_contains()` for cleaner comparison
  - Resolved strict comparison warning for string position checks
- Applied Laravel Pint formatting across all modified files
- All 222 tests passing

### Benefits

- **Better UX:** Confirm dialogs now show loading states during async operations
- **Improved DX:** Vite watch mode is significantly faster without unnecessary static copying
- **Production Ready:** All assets still copy correctly in production builds
- **Cleaner Code:** Consistent formatting and modern PHP 8+ functions
- **Type Safety:** PHPStan level 5 compliance maintained

## [1.5.1] - 2026-01-11

### Fixed

**Module Generator:**
- Fixed Livewire view file not being created when using `synmod-make livewire` command
  - Added automatic view generation for non-inline Livewire components in `ModuleGeneratorService`
  - View files are now properly created in `resources/views/livewire/` directory
  - Inline components (using `--inline` flag) correctly skip view generation

**CLI Tool:**
- Enhanced `synmod-make` help command to show all 33 available commands
  - Organized commands into logical categories:
    - Core Components (8 commands)
    - Request & Validation (2 commands)
    - Events & Jobs (4 commands)
    - API & Resources (4 commands)
    - Advanced (7 commands)
    - Utilities (5 commands)
  - Added previously undocumented commands: component, view, rule, job-middleware, resource, channel, observer, scope, cast, command, enum, interface, trait, config
  - Improved help output formatting with better alignment and categorization
  - Added more comprehensive usage examples

## [1.5.0] - 2026-01-09

### Added

**Documentation:**
- Comprehensive theme system documentation (700+ lines)
  - New `docs/THEMES.md` - Complete theming guide with examples
  - Theme structure and file organization
  - ThemeService API reference with all methods
  - Helper functions documentation (`theme_asset`, `theme_path`, `active_theme`, `theme_service`)
  - Blade directives (`@themeStyles`, `@themeScripts`, `@themeHeaders`)
  - Step-by-step guide for creating custom themes
  - Theme fallback system explanation
  - Asset management with Vite
  - Component library and Alpine.js components
  - Dark mode support and troubleshooting
  - Best practices and real-world examples
- Updated `README.md` with theme system features and quick reference
- Updated `CLAUDE.md` with ThemeService architecture and helpers
- Updated `docs/COMMANDS.md` with `synapps:make-theme` command documentation
- Updated `docs/CONFIGURATION.md` with theme configuration sections
- Updated `docs/HELPERS.md` with theme helper function examples

**Theme Templates:**
- New "Application" theme template added to stubs
  - Modern application-focused layout
  - Complete component library (buttons, cards, forms, etc.)
  - Sidebar menu with dark mode toggle
  - Header navigation with user menu
  - Guest authentication layouts
  - Fully customizable CSS and JavaScript

### Fixed

**Code Quality:**
- Fixed missing return statement in `ThemeService::getLayoutView()` method
- Resolved PHPStan level 5 analysis error
- Applied Pint formatting to `StubRenderer.php`
- All 222 tests passing

**Component Updates:**
- Improved `adv-select.blade.php` component structure
- Enhanced `select.blade.php` component styling
- Updated `SynapseViewServiceProvider` for better theme support

### Changed

**Documentation:**
- All documentation now cross-referenced with proper links
- Consistent formatting and style across all docs
- Designer-friendly examples (no PHP knowledge required for basic customization)
- Developer-friendly API and architecture documentation

### Benefits

- **Complete Documentation:** Developers and designers have comprehensive guides
- **Better Developer Experience:** Clear examples and API references
- **Easier Theming:** Step-by-step instructions for creating custom themes
- **Code Quality:** All static analysis checks passing
- **Stability:** Full test coverage with 222 passing tests

## [1.4.0] - 2026-01-09

### Added

**Theme System:**
- New `ThemeService` for centralized theme management
- `synapps:make-theme` command to generate new themes from templates
- `synapps:reconfig` command for system reconfiguration
- Theme helper functions:
  - `theme_asset()` - Get URL to theme assets
  - `theme_path()` - Get absolute path to theme directory
  - `active_theme()` - Get active theme name
  - `theme_service()` - Get ThemeService instance
- Default theme stubs with complete layout templates:
  - `app.blade.php` - Main application layout
  - `frontend.blade.php` - Frontend layout
  - `guest.blade.php` - Guest/authentication layout
- Theme asset structure (CSS, JS, images, SVG logos)
- Support for theme-specific configuration via `theme.json`

**Helper Functions:**
- `int2roman()` - Convert integers to Roman numerals (I, II, III, IV, etc.)

**Navigation Components:**
- Refactored navigation components into `layouts/nav/` structure:
  - `arrow.blade.php` - Navigation arrow indicators
  - `menu-item.blade.php` - Individual menu items
  - `submenu-item.blade.php` - Submenu items
  - `submenu.blade.php` - Submenu container
- Improved component organization and reusability
- Updated sidebar to use new navigation component structure

### Changed

**Architecture:**
- Enhanced `Apps` configuration builder to support theme settings
- Updated `SynAppsPatchResources` to handle theme assets
- Updated `SynAppsSetup` to initialize theme configuration
- Improved service provider registration for theme support

**Documentation:**
- Removed outdated `RELEASE_CHECKLIST.md`
- Removed `SYNTHEME_IMPLEMENTATION.md` (implementation complete)

### Fixed

**Code Quality:**
- Applied Laravel Pint formatting to new theme commands
- All PHPStan level 5 checks passing
- Improved code organization and structure

### Benefits

- **Theming Support:** Apps can now have multiple themes with easy switching
- **Better Organization:** Navigation components properly structured in layouts
- **Developer Experience:** Simple theme generation with artisan commands
- **Flexibility:** Theme-specific assets and configurations
- **Maintainability:** Centralized theme management through ThemeService

## [1.3.6] - 2026-01-08

### Fixed

**Blade Components:**
- Improved Modal component styling and structure
  - Moved inline `style="display: none;"` to root element for better organization
  - Enhanced modal container positioning and overflow handling
  - Optimized CSS class ordering for consistency
- Cleaned up Toastr component
  - Commented out example trigger buttons to reduce clutter
  - Applied consistent Pint formatting to CSS class order

**Code Quality:**
- Resolved all PHPStan level 5 static analysis errors
  - Added missing `$componentName` property to MakeCommandCommand
  - Fixed boolean operator logic in SynAppsCopyResources (using `|=` operator)
  - Improved `human_filesize()` function to use `log()` instead of `strlen()` on integers
  - Updated `page_title()` PHPDoc to correctly accept `string|array` parameter
- Applied Laravel Pint code formatting
  - Converted `array()` to `[]` syntax in `int2roman()` helper
  - Removed redundant PHPDoc tags covered by type hints
  - Fixed array formatting and spacing consistency

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

[2.2.1]: https://repo.4visionmedia.com:4000/vm-engine/synapse/compare/v2.2.0...v2.2.1
[2.2.0]: https://repo.4visionmedia.com:4000/vm-engine/synapse/compare/v2.1.1...v2.2.0
[2.0.1]: https://repo.4visionmedia.com:4000/vm-engine/synapse/compare/v2.0.0...v2.0.1
[2.0.0]: https://repo.4visionmedia.com:4000/vm-engine/synapse/compare/v1.7.3...v2.0.0
[1.1.0]: https://repo.4visionmedia.com:4000/vm-engine/synapse/compare/v1.0.6...v1.1.0
[1.0.6]: https://repo.4visionmedia.com:4000/vm-engine/synapse/compare/v1.0.5...v1.0.6
[1.0.5]: https://repo.4visionmedia.com:4000/vm-engine/synapse/compare/v1.0.4...v1.0.5
[1.0.4]: https://repo.4visionmedia.com:4000/vm-engine/synapse/compare/v1.0.3...v1.0.4
[1.0.3]: https://repo.4visionmedia.com:4000/vm-engine/synapse/compare/v1.0.2...v1.0.3
[1.0.2]: https://repo.4visionmedia.com:4000/vm-engine/synapse/compare/v1.0.1...v1.0.2
[1.0.1]: https://repo.4visionmedia.com:4000/vm-engine/synapse/compare/v1.0.0...v1.0.1
[1.0.0]: https://repo.4visionmedia.com:4000/vm-engine/synapse/releases/tag/v1.0.0
