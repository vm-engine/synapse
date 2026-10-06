# Upgrade Guide

This guide outlines the upgrade process for major and minor versions of Synapse.

## Table of Contents

- [Versioning Policy](#versioning-policy)
- [Upgrade Process](#upgrade-process)
- [Version-Specific Guides](#version-specific-guides)

---

## Versioning Policy

Synapse follows [Semantic Versioning 2.0.0](https://semver.org/):

### Version Format: MAJOR.MINOR.PATCH

- **MAJOR** (1.x.x) - Breaking changes, incompatible API modifications
- **MINOR** (x.1.x) - New features, backward compatible additions
- **PATCH** (x.x.1) - Bug fixes, backward compatible fixes

### Stability Commitment

#### v1.x - Stable Branch

Once v1.0.0 is released:

✅ **Guaranteed Stability**
- No breaking changes within v1.x series
- All new features will be backward compatible
- Deprecation warnings before removal (min 2 minor versions)

⚠️ **Deprecation Process**
1. Feature marked as deprecated in version X.Y.0
2. Deprecation warning displayed in logs
3. Feature removed in version X.(Y+2).0 at earliest

📅 **Support Timeline**
- **Active Support:** Latest MAJOR version (e.g., v1.x)
- **Security Fixes:** Latest MAJOR + previous MAJOR (e.g., v1.x and v0.x)
- **Support Duration:** 18 months after new MAJOR release

#### Pre-1.0 (Beta/Alpha)

⚠️ **No Stability Guarantee**
- Breaking changes may occur between minor versions
- API may change without notice
- Use in production at your own risk

---

## Upgrade Process

### Before You Upgrade

If your project is still on an old `vm-engine/synapse` version, audit it first for legacy patterns that will need updating:

- **Already on a version that ships `synapps:upgrade-audit`?** Run:
  ```bash
  php artisan synapps:upgrade-audit
  ```
  This reports findings only (no file changes) and writes `SYNAPSE_UPGRADE_AUDIT.md`. Pass `--apply` to auto-fix the mechanical findings once you're ready.

- **Stuck on a version too old to have that command?** Copy the self-contained standalone script into your project root and run it directly — no `composer update` required first:
  ```bash
  php synapse-upgrade-audit.php --path=.
  ```
  The script lives at `vendor/vm-engine/synapse/tools/synapse-upgrade-audit.php` (or in this repo at `packages/synapse/tools/synapse-upgrade-audit.php`). It's audit-only, makes no changes, and detects the same breaking-change patterns documented in the version-specific guides below.

See [docs/COMMANDS.md](docs/COMMANDS.md#synappsupgrade-audit) for full command/script documentation.

### General Upgrade Steps

0. **Run the upgrade audit** (see above) to see what needs attention before you touch `composer.json`.

1. **Review the CHANGELOG**
   ```bash
   # Check what changed in the new version
   cat vendor/vm-engine/synapse/CHANGELOG.md
   ```

2. **Check Breaking Changes**
   - Read the "Breaking Changes" section in CHANGELOG
   - Review deprecated features you might be using

3. **Update Composer**
   ```bash
   # For specific version
   composer require vm-engine/synapse:^2.0

   # Or update to latest
   composer update vm-engine/synapse
   ```

4. **Run Discovery and Regenerate Config**
   ```bash
   php artisan synapps:discover
   php artisan synapps:config
   ```

5. **Test Your Application**
   ```bash
   composer test
   php artisan test
   ```

6. **Review Module Compatibility**
   - Check if your custom modules need updates
   - Verify all module service providers still work

---

## Version-Specific Guides

### Upgrading to v1.0.0 (From Beta)

#### Breaking Changes

**None** - v1.0.0 is the first stable release.

#### New Features

All features from beta are now stable. See [CHANGELOG.md](CHANGELOG.md) for full list.

#### Required Actions

1. **Update composer.json:**
   ```json
   {
       "require": {
           "vm-engine/synapse": "^1.0"
       }
   }
   ```

2. **Run setup commands:**
   ```bash
   composer update vm-engine/synapse
   php artisan synapps:discover
   php artisan synapps:config
   ```

3. **Update module.json files** (if using old format):
   - Ensure all modules have proper `id_case` field
   - Verify `load_priority` is set (default: 99)

4. **Test module loading:**
   ```bash
   php artisan route:list  # Verify all module routes loaded
   php artisan config:show synapps  # Verify config compiled
   ```

#### Recommendations

- ✅ Review and update `.gitignore` to exclude compiled configs
- ✅ Set up testing with `synapps:patch tests`
- ✅ Configure error views with `synapps:link-errors`

---

### Upgrading to v2.0.0

#### Breaking Changes

**Livewire v4 Upgrade:**
- Livewire dependency changed from `^3.0` to `^4.0`
- Stub components converted from class-based (`src/Livewire/`) to MFC format (`resources/views/components/`)
- Stub routes migrated from `Route::get(Component::class)` to `Route::livewire(Component::class)`
- JavaScript hooks migrated from Livewire v3 API to v4 interceptor pattern

**Required Actions:**

1. **Update composer.json:**
   ```json
   {
       "require": {
           "vm-engine/synapse": "^2.0",
           "livewire/livewire": "^4.0"
       }
   }
   ```

2. **Migrate JS hooks** — replace v3 hooks with v4 interceptor pattern:
   ```js
   // Before (v3)
   Livewire.hook('commit', ({ component, commit, respond, succeed, fail }) => { ... })
   Livewire.hook('morph.updated', ({ el, component }) => { ... })
   Livewire.hook('message.processed', (message, component) => { ... })

   // After (v4)
   Livewire.interceptMessage(component, message, ({ respond, succeed, fail }) => {
       succeed(({ snapshot, effect }) => { ... })
   })
   ```

3. **Migrate routes** — replace `Route::get()` with `Route::livewire()` for full-page components:
   ```php
   // Before
   Route::get('/dashboard', Dashboard::class);

   // After
   Route::livewire('/dashboard', Dashboard::class);
   ```

4. **Update component references** — Blade facade calls should use `app('blade.compiler')` directly (see changelog for details if you extend `SynapseViewServiceProvider`).

5. **Run setup commands:**
   ```bash
   composer update vm-engine/synapse
   php artisan synapps:discover
   php artisan synapps:config
   ```

---

### Upgrading to v2.1.0

#### New Features (non-breaking)

**RemembersQueryParams Trait:**
- New `RemembersQueryParams` trait for Livewire components that persists declared properties (search, filters, pagination) to the session
- Automatically restores list state when navigating back
- No changes required — opt-in by using the trait

---

### Upgrading to v2.2.0

#### Breaking Changes

**Livewire Component Namespace Notation:**
- Component tags change from dot notation to namespace notation
- `<livewire:example.todo-form />` → `<livewire:example::todo-form />`
- `<livewire:example.forms.contact-form />` → `<livewire:example::forms.contact-form />`

**AutoRegistersComponents Trait:**
- `registerMfcComponents()` and `registerLivewireComponents()` replaced by `registerLivewireNamespace()`
- New method uses Livewire v4's native `addNamespace()` for automatic component discovery

**Required Actions:**

1. **Update all component tags** in Blade views from dot notation to namespace notation:
   ```blade
   {{-- Before --}}
   <livewire:example.todo-form />
   <livewire:example.forms.contact-form />

   {{-- After --}}
   <livewire:example::todo-form />
   <livewire:example::forms.contact-form />
   ```

2. **Update `Route::livewire()` calls** if using dot notation:
   ```php
   // Before
   Route::livewire('/todo', 'example.todo-form');

   // After
   Route::livewire('/todo', 'example::todo-form');
   ```

3. **Update service provider** if calling registration methods directly:
   ```php
   // Before
   $this->registerMfcComponents();
   $this->registerLivewireComponents();

   // After
   $this->registerLivewireNamespace();
   // Or simply:
   $this->registerComponents(); // handles everything
   ```

4. **Update Livewire component references in tests** to namespace notation.

5. **Run setup commands:**
   ```bash
   composer update vm-engine/synapse
   php artisan synapps:discover
   php artisan synapps:config
   ```

---

### Upgrading to v3.0.0

Changes below are sorted by impact — work through **Critical** first; each item blocks or silently breaks something if skipped. See [CHANGELOG.md](CHANGELOG.md#300---2026-10-06) for full technical detail on every item.

#### 🔴 Critical

**1. `<x-synapse-select>` / `<x-synapse-multiselect>` / Select2 removed.**
Replaced entirely by `<x-adv-select>` (single and multiple selection, static or remote search). If any of your own views still render the old tags, they will throw an "unknown component" error at render time — this isn't a deprecation, the classes and views are deleted.

- Find every usage: `grep -rn "x-synapse-select\|x-synapse-multiselect" resources/ synapps/`
- Migrate each to `<x-adv-select>` — see [docs/COMPONENTS.md#advanced-select](docs/COMPONENTS.md#advanced-select) for the full prop reference. A single select becomes:
  ```blade
  {{-- Before --}}
  <x-synapse-select :options="['a' => 'A', 'b' => 'B']" wire:model="type" />

  {{-- After --}}
  <x-adv-select wire-model="type" :options="[
      ['value' => 'a', 'label' => 'A'],
      ['value' => 'b', 'label' => 'B'],
  ]" />
  ```
  A multi-select adds `:multiple="true"`.
- Do this migration **before** updating `composer.json` to `^3.0` — the old components are gone the moment you upgrade, so migrate on the current version first, verify, then bump.

**2. `system.json` config system removed entirely.**
`synapps/config/system.json`, `system.dev.json`, the compiled `synapps/config/compiled/system.json`, the `System` config builder, and `config('synapps.system.*')` are all gone.

- Delete the three files: `synapps/config/system.json`, `synapps/config/system.dev.json`, `synapps/config/compiled/system.json`.
- Run `php artisan synapps:config` to regenerate.
- If you relied on `synapps.system.public_path` to override the app's public directory, that override is gone — reconfigure it another way (e.g. your own service provider binding `path.public`).
- If you had a custom `binding` key in `system.json` for `mergeBinding()`-style container overrides, that mechanism is also removed — move the binding into your own service provider.

**3. Requires PHP ^8.5 and Laravel ^13.0** (dropped `12.x` support).
```json
{
    "require": {
        "php": "^8.5",
        "laravel/framework": "^13.0",
        "vm-engine/synapse": "^3.0"
    }
}
```
Upgrade PHP and Laravel first, confirm your app boots and its own test suite passes, **then** upgrade this package.

#### 🟠 High

**4. Alpine.js is now CSP-safe by default.**
`SynapseServiceProvider` auto-forces `config('livewire.csp_safe') = true` on boot, and `SecureHeaders`'s `script-src` no longer includes `unsafe-eval`. This is good for security, but Alpine's CSP-safe parser rejects inline `x-data="{ ... }"` object literals with methods/arrow-functions, multi-statement directive expressions (e.g. `x-init="a(); b(); c()"`), and `@entangle()`.

- Audit your own Blade views (not just this package's, which are already migrated) for inline `x-data` objects with methods, multi-statement attribute expressions, or `@entangle()`.
- Migrate offending components to Alpine's `Alpine.data('name', () => ({ ... }))` registry pattern, called via `x-data="name"`.
- If you need the old (less strict) behavior temporarily while migrating incrementally, override `livewire.csp_safe` back to `false` in your own `config/livewire.php` — but treat this as a stopgap, not a destination; re-enabling `unsafe-eval` weakens your CSP.
- To find candidates: `grep -rn 'x-data="{' resources/ synapps/` and check for `=>`/`function`/`;` inside the match.

**5. `synapps:patch-resources` and `synapps:patch-tests` replaced by `synapps:patch {object?*}`.**
Option flags became positional object arguments; the two commands merged into one.

| Before | After |
|---|---|
| `synapps:patch-resources --theme` | `synapps:patch theme` |
| `synapps:patch-resources --css-sources` | `synapps:patch css` |
| `synapps:patch-resources --npm-watch` | `synapps:patch npm` |
| `synapps:patch-resources --js-back` | `synapps:patch js` |
| `synapps:patch-resources --vite` | `synapps:patch vite` |
| `synapps:patch-resources --split-layouts` | `synapps:patch layouts` |
| `synapps:patch-resources --comment-default` | `synapps:patch seeder` |
| `synapps:patch-tests` | `synapps:patch tests` |
| `synapps:patch-resources` (no flags, full run) | `synapps:patch` (no argument, full run) |

Update any deployment scripts, CI pipelines, or Composer scripts that invoke the old command names/flags directly.

#### 🟡 Medium

**6. `synapps:make-*` individual commands are hidden from `artisan list` (still fully functional).**
`synapps:make {object} {module} {name?}` is now the documented primary entry point (e.g. `synapps:make model blog Post -mfs`), replacing 34 individually-listed commands as the "front door." The old direct commands (`synapps:make-model blog Post -mfs`, etc.) still work exactly as before — nothing to change unless you want to switch to the shorter unified form. See [docs/MODULE-GENERATOR.md](docs/MODULE-GENERATOR.md).

**7. `<x-adv-select>` markup reworked.**
The search input moved inside the dropdown (was previously nested in the bordered main box), gained a rotating dropdown-arrow indicator, and its clear/pill-remove buttons switched from a bare `✕` glyph to Phosphor icons. No prop/API changes — but if you have custom CSS targeting `adv-select`'s internal DOM structure (not just the documented props), re-check it against the new markup.

#### 🟢 Low (no action required)

- **New components:** `<x-synapse-badge>`, `<x-synapse-panel>` (with `collapsible`), `<x-synapse-search-box>`, `<x-synapse-stat-tile>` — purely additive. See [docs/COMPONENTS.md](docs/COMPONENTS.md).
- **New in-app notification system** (`Notify` facade, `synotif:setup` command, `<livewire:synotif::bell />`) — disabled by default, opt-in via `php artisan synotif:setup`. See [docs/COMMANDS.md#synotifsetup](docs/COMMANDS.md#synotifsetup).
- **Bug fixes** with no required action unless you were relying on the buggy behavior: welcome-route detection on Laravel 13's skeleton, a stray `synapps.css` re-import in the CSS split-layout rewrite, `is_decimal()`'s `TypeError` under strict types, `synapps:make-observer`'s missing model import, and tenancy central migrations now included in `migration_path` automatically when tenancy is enabled.
- **Package-internal only, no consumer impact:** PHPStan raised from level 6 to 7, `declare(strict_types=1)` added across the package's own source.

#### Quick Checklist

- [ ] Migrate all `<x-synapse-select>`/`<x-synapse-multiselect>` usages to `<x-adv-select>` **on the current version**, before bumping composer.json
- [ ] Delete `synapps/config/system.json`, `system.dev.json`, `compiled/system.json`; run `php artisan synapps:config`
- [ ] Upgrade PHP to 8.5+ and Laravel to 13.x
- [ ] Audit custom Blade views for CSP-unsafe inline `x-data`/`@entangle()` usage
- [ ] Update any scripts calling `synapps:patch-resources --flag` / `synapps:patch-tests` to the new `synapps:patch {object}` form
- [ ] `composer require vm-engine/synapse:^3.0`
- [ ] `php artisan synapps:discover && php artisan synapps:config`
- [ ] Run your test suite

---

## Deprecation Warnings

Synapse uses Laravel's logging system to warn about deprecated features:

```php
// Example deprecation warning in logs
[WARNING] The 'old_method()' is deprecated and will be removed in v1.5.0.
          Use 'new_method()' instead.
```

**How to check for deprecations:**

1. **Review Laravel logs:**
   ```bash
   php artisan pail --filter=deprecated
   ```

2. **Check your code:**
   ```bash
   grep -r "deprecated" storage/logs/
   ```

3. **Update before removal:**
   - Replace deprecated methods
   - Test thoroughly
   - Deploy before the removal version

---

## Breaking Changes Policy

### What Constitutes a Breaking Change?

**Breaking changes** require a MAJOR version bump:

❌ **Breaking:**
- Removing public methods or properties
- Changing method signatures (parameters, return types)
- Removing Blade components
- Changing configuration file structure
- Removing helper functions
- Changing command signatures

✅ **Not Breaking:**
- Adding new optional parameters with defaults
- Adding new methods or properties
- Adding new Blade components
- Deprecating features (with warnings)
- Internal refactoring
- Bug fixes that restore intended behavior

### Handling Breaking Changes

When breaking changes are necessary:

1. **Announce in advance** (min 3 months for major features)
2. **Provide migration path** in documentation
3. **Include automated migration tools** when possible
4. **Comprehensive changelog** with upgrade instructions

---

## Module Compatibility

### Module Version Constraints

When creating modules, specify compatible Synapse versions in `module.json`:

```json
{
    "id": "my-module",
    "name": "My Module",
    "dependencies": {
        "composer": {
            "main": {
                "vm-engine/synapse": "^1.0"
            }
        }
    }
}
```

### Compatibility Table

| Synapse Version | Laravel | Livewire | PHP |
|-----------------|---------|----------|-----|
| v1.0.x – v1.7.x | ^12.0  | ^3.0     | >=8.2 |
| v2.0.x – v2.4.x | ^12.0  | ^4.0     | >=8.2 |
| v3.0.x+         | ^13.0  | ^4.0     | ^8.5  |

---

## Rollback Strategy

If you encounter issues after upgrading:

### 1. Rollback Composer

```bash
# Rollback to specific version
composer require vm-engine/synapse:1.0.0

# Rollback using composer.lock
git checkout HEAD~1 composer.lock
composer install
```

### 2. Clear Caches

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### 3. Revert Config Changes

```bash
# If you modified configs
git checkout HEAD~1 config/
git checkout HEAD~1 synapps/config/
php artisan synapps:config
```

### 4. Report Issues

If rollback was necessary, please report:
- Version you upgraded from
- Version you upgraded to
- Error messages
- Steps to reproduce

---

## Getting Help

- **Documentation:** [README.md](README.md)
- **Issue Tracker:** Report bugs and request features
- **Support:** Contact VM Engine development team

---

## Contributing to Upgrades

Help improve the upgrade experience:

1. **Test pre-releases** and report issues
2. **Document edge cases** you encounter
3. **Suggest automated migrations** for breaking changes
4. **Improve this guide** with your experiences
