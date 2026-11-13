# Artisan Commands

Synapse provides several Artisan commands for managing your modular application.

## Quick Reference

| Command | Purpose | When to Run | Idempotent | Auto-run by Setup |
|---------|---------|-------------|------------|-------------------|
| `synapps:setup` | Initial setup wizard | Once on fresh install | No (overwrites) | N/A |
| `synapps:config` | Regenerate config files | After config changes | Yes | ✅ Yes |
| `synapps:discover` | Module discovery & deps | After module changes | Yes | ✅ Yes |
| `synapps:patch` | Patch JS/CSS/Vite/routes/seeder/tests | After install, if assets missing | Yes | ✅ Yes |
| `synapps:link-errors` | Link error views | After custom error pages | Yes | ✅ Yes |
| `synapps:copy-stubs` | Copy stub modules into synapps/modules | When you need example module code | Yes | No |
| `synapps:make` | Create a component in a synapps/modules module | Building out a module | Yes | No |
| `synapps:make-theme` | Create new theme from template | When creating custom themes | Yes | No |
| `synapps:remove-module` | Move a module to `.trash` (or delete) and rediscover | Retiring a module | No | No |
| `synapps:copy-resources` | Re-copy layouts, CSS, JS, assets from stubs | Refreshing stub resources after an upgrade | Yes (asks before overwrite) | No |
| `synapps:copy-themes` | Copy stub themes into `synapps/resources/themes` | Refreshing bundled themes | Yes | No |
| `synapps:searchable-setup` | Enable global search (table + import) | Turning on the Ctrl/⌘+K palette | Yes | No |
| `synapps:searchable-forget` | Delete searchable entries by source prefix | Cleaning stale search content | Yes | No |
| `synapps:reconfig` | Interactively update `apps.json` | Changing app code/domains/dependencies after setup | Yes | No |
| `synotif:setup` | Enable/configure in-app notifications | Turning on notifications after setup | Yes | No |
| `synapps:vendor-fonts` | Self-host a theme's Google Fonts | Removing third-party font CDN calls | Yes (per theme) | No |
| `synapps:publish-settings` | Publish Settings module | When you need settings module | Yes | No |
| `synapse:db-config` | Manage database configuration | When updating configs | Yes | No |
| `synapse:db-lang` | Manage database translations | When updating translations | Yes | No |
| `synapse:excel-cleanup` | Mark stale Excel tasks as failed | Scheduled or manual | Yes | No |
| `synapps:upgrade-audit` | Detect (and optionally auto-fix) legacy Synapse patterns | Before/while upgrading vm-engine/synapse | Yes | No |

> **Note:** If you run `synapps:setup`, you don't need to manually run the other commands unless you're troubleshooting or have specific requirements.

---

## synapps:setup

Interactive setup wizard for new installations. This is the primary command for setting up Synapse in your Laravel project.

```bash
php artisan synapps:setup
```

### What it does

1. Prompts for project configuration (app code, name, author, URLs, etc.)
2. Copies stub directory from `vendor/vm-engine/synapse/src/stubs/synapps/` to `synapps/`
3. Generates `synapps/config/apps.json` configuration file
4. **Automatically executes these commands in sequence:**
   - `vendor:publish --tag=synapse-config --force` - Publishes Synapse configuration files
   - `synapps:config` - Regenerates SynApps & Synapse configuration files
   - `synapps:link-errors` - Links custom error views
   - `synapps:patch` - Patches JS/CSS imports and configures testing infrastructure
   - `synapps:discover` - Discovers modules and updates dependencies

One of the prompts is **"Enable the rich-text editor?"** (default no). Yes sets `rte.enabled` in `apps.json`, adds `jodit` (npm) and `mews/purifier` (composer) to its `dependencies` block so `synapps:discover` installs them, and imports `synapps/resources/js/synapse-rte.js` into `resources/js/app.js`. See [COMPONENTS.md → Rich-Text Editor](COMPONENTS.md#rich-text-editor-optional).

### Configuration files published

- `config/synapse.php` - Core Synapse configuration
- `config/synapps.php` - Application configuration

### When to run

Once on fresh Laravel installation, or when initializing Synapse.

---

## synapps:config

Regenerates SynApps and Synapse configuration files by running both configuration generators.

```bash
php artisan synapps:config
```

### What it does

1. Runs **SynAppsConfigGenerator** to generate `config/synapps.php`
2. Runs **SynapseConfigGenerator** to generate `config/synapse.php`
3. Reads source configs from `synapps/config/{apps,modules}.json` and `.dev.json` overrides
4. Executes all config builders (Apps, Modules, Binding) through their lifecycle phases
5. Generates compiled configs in `synapps/config/compiled/`

### When to run

- After modifying any `synapps/config/*.json` files
- After adding or removing modules
- After changing module configurations
- After updating system or app settings
- **Instead of `config:cache`** when working with Synapse modular configurations

### Important

This command replaces `php artisan config:cache` in Synapse projects, as it regenerates module-specific configurations. Note that `synapps:setup` and `synapps:discover` automatically call this command, so you don't need to run it manually after those operations.

---

## synapps:discover

Scans for modules and manages project dependencies automatically.

```bash
php artisan synapps:discover
```

### What it does

1. Scans `synapps/modules/` and `vendor/*/synmod-*` for `module.json` files
2. Generates PSR-4 autoload namespaces in `composer.json` (3 per module: App, Seeders, Factories)
3. Aggregates npm/composer dependencies from all modules
4. Smart merges dependencies using semver (only upgrades versions). Composer constraints are compared on their parsed lower bound; a module's branch constraint (`dev-main`, `@dev`) never overwrites a stable constraint in your `composer.json` — it warns instead
5. Runs `composer update <only the packages whose constraint changed> --with-all-dependencies`, or `composer dump-autoload` when nothing but the autoload map changed. It never runs a bare `composer update`
6. Runs `npm install` if `package.json` changed, then `npm run build`
7. Reports any `composer`/`npm` command that exited non-zero and exits non-zero itself. The manifests are **not** rolled back — if `vendor/` is left incomplete, run `composer install --no-scripts` and re-link any vendor package you keep as a symlink

### When to run

- After creating new modules
- After modifying `module.json` files
- After changing module dependencies
- **Automatically called by `synapps:setup`**

---

## synapps:patch

Integrates Synapse resources into your Laravel application, wires up the per-layout CSS/JS split (since v2.4.0), and cleans up default Laravel files.

```bash
php artisan synapps:patch
```

With no arguments, **all patch operations run**, including the per-layout split — this brings a project all the way from a fresh Laravel install to the current 8-entry-point structure in one call.

### Arguments

Pass one or more object names to run only that subset of patches (useful for re-running a single patch after manually reverting a file):

```bash
php artisan synapps:patch layouts
php artisan synapps:patch theme vite
```

| Object | Restricts to |
|---|---|
| `theme` | Legacy monolith theme import into `app.css`/`app.js` (only applies if a theme still has a single `theme.css`/`theme.js`, pre-2.4.0 style) |
| `seeder` | Commenting out the default Laravel welcome route and `User::factory()` seeder call |
| `css` | Tailwind `@source` directives in `resources/css/app.css` |
| `npm` | `package.json` `"watch": "vite build --watch"` script |
| `js` | `window.withBack()` global JS helper in `resources/js/app.js` |
| `vite` | `vite.config.js` static-asset-copy plugin + dev-server watch config |
| `layouts` | Per-layout CSS/JS split only (see below) |
| `tests` | Testing infrastructure only (see below) |

### `--force` — reinstalling the package-owned files

Most of this command's file creations are skip-if-exists: both
`synapps/resources/js/synapse-components.js` and `synapps/resources/css/synapse-components.css`
are copied once and then left alone forever, so a fix shipped in the package's Alpine or
component CSS never arrives through the command that installed them.

> **Overlaps with `synapps:copy-resources`.** `copy-resources --js --css --force` also replaces
> these two files, and is the right tool for a broad "give me the current stubs" refresh during
> an upgrade. It copies whole stub directories, so asking it for the components file also rewrites
> `synapps.js`, `synapse-rte.js`, `sb-admin-2.min.js` and `synapps.css`, and it writes no backup.
> Use `patch --force` when you want only the component files and a way back.

`--force` reinstalls exactly those two files from the current package stubs:

```bash
php artisan synapps:patch --force        # both files
php artisan synapps:patch js --force     # same two files — --force ignores the object filter
npm run build                            # required — these are bundled assets
```

`--force` applies to those two files whatever object you name, so you do not have to know that
they happen to live in the `layouts` subset. The named object still decides everything else: the
command above forces the component files and patches `resources/js/app.js`, and creates none of
the layout entry files.

The previous file is kept as `.bak` next to it. That backup is written **once**, so it stays
pinned to the project's own copy rather than tracking the last forced write — a later `--force`
replaces package content with package content, where there is nothing to preserve.

`--force` never *overwrites* the host-owned entry points: `resources/css/{base,app,frontend,guest}.css`,
`resources/js/{base,app,frontend,guest}.js` and `vite.config.js` hold your `@source` directives, Vite
inputs and global JS helpers. The command prints this list on every forced run. The regular patches
in the same run (the focused-import rewrite, Vite inputs, `@source` lines) still apply to them as
they do without `--force` — check the `Patched:` lines.

If you customised `synapse-components.js` in place, diff it against the `.bak` after forcing and
re-apply your changes — ideally as a separate file imported from `synapps.js`, so the next
`--force` costs nothing.

### What it does (no arguments — full run)

1. **Base synapps imports** — adds `import '../../synapps/resources/js/synapps.js';` to `resources/js/app.js` and `@import '../../synapps/resources/css/synapps.css';` to `resources/css/app.css`
2. **Tailwind `@source` directives** — adds `@source` entries covering `synapps/`, active theme, and `synmod-*`/`synapps-*` vendor package Blade/JS files
3. **Legacy theme import** (`theme`) — only if the active theme still has a single `theme.css`/`theme.js` (pre-split); no-ops for themes already using the 4+4 split structure
4. **`vite.config.js`** — converts to function-based config (for `--watch` detection), adds the `vite-plugin-static-copy` plugin (wrapped so it's skipped during `--watch` for faster HMR) to copy assets from `synapps/resources/assets/`, per-theme `assets/`, and `vendor/*/synmod-*` / `vendor/vm-engine/synapps-*` packages, and ensures `server.watch` has the right `ignored` paths + polling config for cross-platform file watching
5. **`routes/web.php`** — comments out the default Laravel welcome route, but only if no other routes are defined in the file
6. **`database/seeders/DatabaseSeeder.php`** — comments out the default `User::factory()` call (handles multi-line calls too)
7. **`package.json`** — adds a `"watch": "vite build --watch"` script
8. **`resources/js/app.js`** — appends the global `window.withBack()` helper plus a `mousedown` interceptor that recomputes the `?back=` URL at click time (fixes stale URLs after Livewire pagination)
9. **Per-layout CSS/JS split** (`layouts`) — see below

### Per-layout CSS/JS split (`layouts`)

Brings the project from the single `app.css`/`app.js` monolith to the 8-entry-point structure introduced in v2.4.0:

1. Creates `synapps/resources/css/synapps-{base,app,frontend,guest}.css` and matching `.js` files — each imports the active theme's corresponding `theme-{layout}.css`/`.js` split file
2. Creates `resources/css/{base,frontend,guest}.css` and `resources/js/{base,frontend,guest}.js` if missing — `base.*` is migrated from the existing monolithic `app.*` content (its `synapps.css`/`.js` import is repointed to `synapps-base.*`, and any direct `theme.css`/`.js` import is stripped since `theme-base.*` now handles it)
3. **Rewrites** `resources/css/app.css` and `resources/js/app.js` to a focused import of `synapps-app.css`/`.js` — detected by the presence of the direct `synapps.css`/`.js` monolith import, so it only rewrites files still in the old form. Lines the new `base.*` does not already carry (a module's own script, `synapse-rte.js`, …) are kept below the focused import, never dropped. A full run never re-adds the monolith import to a file that is already split (before 3.10.1 it did, and the rewrite then discarded the host's imports).
4. Patches the active-theme import line in all 8 `synapps-*.css`/`.js` files whenever the active theme changes (e.g. after `synapps:reconfig` or editing `apps.json` directly) — idempotent, only writes when the theme path actually differs
5. Expands `vite.config.js`'s `input` array to all 8 entry points (`resources/{css,js}/{base,app,frontend,guest}.*`)

> **Breaking in v2.4.0:** themes without the 4+4 split (`theme-{base,app,frontend,guest}.{css,js}`) will fail `npm run build` once `layouts` has run, since `synapps-base.css` imports `theme-base.css`. See the [Theme System docs](../docs/THEMES.md) for the split-theme layout.

### Backup files

Before modifying any file, the command creates a `.bak` backup (e.g. `app.js.bak`, `vite.config.js.bak`). The backup is only created once — subsequent runs will not overwrite it.

### Patched file reporting

Each successfully patched file/operation is reported individually:

```
Patched: app.js — synapps import
Patched: app.css — synapps import
Patched: vite.config.js
Patched: app.js — global helpers
Patched: resources/css/base.css
Patched: resources/js/base.js
Patched: resources/css/app.css — rewrite to focused import
Patched: resources/js/app.js — rewrite to focused import
Patched: vite.config.js — expand input array
```

### Testing infrastructure (`tests`)

Configures comprehensive testing infrastructure for Synapse modules.

```bash
php artisan synapps:patch tests
```

#### What it does - patches 4 files

1. **`tests/Pest.php`** - Adds module test discovery paths:
   ```php
   '../synapps/modules/*/tests/Feature'
   '../vendor/*/synmod-*/tests/Feature'
   'Browser' // Pest Browser Testing
   ```

2. **`.env.testing`** - Creates with in-memory SQLite configuration:
   ```env
   DB_DATABASE=:memory:
   CACHE_STORE=array
   QUEUE_CONNECTION=sync
   ```

3. **`composer.json`** - Updates test script:
   ```json
   "test": "php artisan config:clear && php artisan config:cache && php artisan test --env=testing"
   ```

4. **`phpunit.xml`** - Adds 3 test suites (Browser, Modules, Packages) with proper exclusions

#### Run test suites

```bash
php artisan test                        # All tests
php artisan test --testsuite=Browser    # Browser tests only
php artisan test --testsuite=Modules    # Module tests only
php artisan test --testsuite=Packages   # Package tests only
```

### Safe to run multiple times

Every operation is idempotent — only patches if the change is not already present, so re-running (with or without arguments) is always safe.

### When to run

- After fresh installation
- If Synapse assets are not loading
- After switching the active theme, to repropagate the theme import lines (`layouts`, or just re-run with no arguments)
- After adding new modules with tests
- **Automatically called by `synapps:setup`**

### See Also

- [Theme System Documentation](../docs/THEMES.md) - Per-layout theme file structure
- [synapps:reconfig](#synapps-reconfig) - Changing the active theme without resetting it

---

## synapps:link-errors

Creates a symlink/junction from Laravel's error views to Synapse's custom error pages.

```bash
php artisan synapps:link-errors
```

### What it does

- Creates symlink: `resources/views/errors` → `synapps/resources/views/errors`
- Tries symlink first, falls back to Windows junction (no admin required)
- Auto-removes existing link/directory before creating new one

### When to run

- After fresh installation
- After customizing error pages
- **Automatically called by `synapps:setup`**

---

## synapps:copy-stubs

Copies one or more stub modules from the Synapse package into `synapps/modules/` to give you a working starting point.

```bash
php artisan synapps:copy-stubs
```

### Arguments

- `module*` - Optional list of module names to copy (e.g. `home todos`). When omitted, an interactive multi-select prompt lets you pick from the available stubs.

### Options

- `--force` - Overwrite existing module directories (default: skipped with a warning)

### Available stubs

```bash
# See what's available
php artisan synapps:copy-stubs
# → Interactive prompt lists all available stubs
```

Stubs are shipped inside the Synapse package at `vendor/vm-engine/synapse/stubs/synapps/modules/`.

### Examples

```bash
# Interactive selection
php artisan synapps:copy-stubs

# Copy specific modules
php artisan synapps:copy-stubs home todos

# Overwrite existing
php artisan synapps:copy-stubs todos --force
```

### After copying

Run `php artisan synapps:discover` to register the new modules and update autoloading:

```bash
php artisan synapps:discover
```

### When to run

- When you want a working reference module to learn from or customise
- When scaffolding a new project and you want the `todos` demo module
- When you need to reset a stub module back to its original state (`--force`)

---

## synapps:make-theme

Create a new theme from an existing theme template.

```bash
php artisan synapps:make-theme {name} {--copy-from=default}
```

### Arguments

- `name` - Theme name (will be converted to slug format)

### Options

- `--copy-from=default` - Source theme to copy from (default: `default`)

### What it does

1. Creates new theme directory at `synapps/resources/themes/{name}/`
2. Copies entire theme structure from source theme
3. Updates `theme.json` with new theme metadata:
   - Sets `id` to theme name
   - Sets `name` to human-readable title
   - Resets `version` to 1.0.0
   - Clears `author` field for customization

### Examples

```bash
# Create theme from default template
php artisan synapps:make-theme my-theme

# Create theme based on another custom theme
php artisan synapps:make-theme dark-theme --copy-from=custom-theme
```

### Next Steps

After creating a theme:

1. **Edit theme metadata**: `synapps/resources/themes/{name}/theme.json`
2. **Customize styles**: `synapps/resources/themes/{name}/css/theme-{base,app,frontend,guest}.css`
3. **Update layouts**: `synapps/resources/themes/{name}/layouts/*.blade.php`
4. **Add Alpine.js components**: `synapps/resources/themes/{name}/js/theme-{base,app,frontend,guest}.js`
5. **Replace logo**: `synapps/resources/themes/{name}/assets/images/logo.svg`
6. **Activate theme** in `synapps/config/apps.json`:
   ```json
   {
     "themes": {
       "active": "my-theme",
       "fallback": "default"
     }
   }
   ```
7. **Regenerate config**: `php artisan synapps:config`
8. **Build assets**: `npm run build`

### Theme Structure

The command creates this directory structure:

```
synapps/resources/themes/{name}/
├── theme.json              # Theme metadata and CDN resources
├── README.md               # Theme documentation
├── assets/
│   ├── images/
│   │   ├── logo.svg       # Light mode logo
│   │   └── logo-dark.svg  # Dark mode logo
│   └── fonts/             # Custom web fonts (optional)
├── css/
│   ├── theme-base.css     # Shared styles (every page)
│   ├── theme-app.css      # Backend-only styles
│   ├── theme-frontend.css # Frontend-only styles
│   └── theme-guest.css    # Auth-only styles
├── js/
│   ├── theme-base.js      # Shared Alpine components (every page)
│   ├── theme-app.js       # Backend-only Alpine components
│   ├── theme-frontend.js  # Frontend-only JS
│   └── theme-guest.js     # Auth-only JS
└── layouts/
    ├── app.blade.php      # Backend/admin layout
    ├── frontend.blade.php # Public website layout
    └── guest.blade.php    # Authentication pages layout
```

### When to run

- When creating a new custom theme
- When duplicating an existing theme for modification
- When setting up a multi-tenant application with theme per tenant

### Related Commands

- `php artisan synapps:config` - Regenerate config after activating theme
- `npm run build` - Build theme assets for production
- `npm run dev` - Build theme assets with hot reload

### See Also

- [Theme System Documentation](../docs/THEMES.md) - Complete theming guide
- [Configuration](../docs/CONFIGURATION.md) - Theme configuration options

---

## synapps:remove-module

```bash
php artisan synapps:remove-module {moduleName} [--permanent] [--force]
```

Removes `synapps/modules/{moduleName}` after confirmation (skipped with `--force` or `--no-interaction`). By default the folder is **moved** to `.trash/{moduleName}-{timestamp}` (and `/.trash` is added to `.gitignore`) so it can be restored by hand; `--permanent` deletes it. Afterwards it rediscovers modules so `composer.json` autoload entries and the compiled configs no longer reference it.

---

## synapps:copy-resources / synapps:copy-themes

```bash
php artisan synapps:copy-resources [--css] [--js] [--layouts] [--assets] [--all] [--force]
php artisan synapps:copy-themes [theme ...] [--force]
```

Re-copy the stub layouts (incl. nav components), CSS, JS and static assets, or the bundled themes, into `synapps/resources`. Without options everything is copied; existing files are only overwritten after confirmation or with `--force`. Use after upgrading synapse when you want the new stub versions (e.g. the impersonation-banner slot in the layout stubs).

`copy-resources` **merges**: it replaces the files the stubs ship and leaves every other file alone. That matters because `synapps/resources/css` and `/js` also hold host-only files — the per-layout `synapps-{app,base,frontend,guest}.css/.js` entry points that `synapps:patch` creates and Vite builds from. A file the stubs no longer ship is therefore left behind rather than removed; delete the directory yourself first if you want a clean reset.

`copy-themes --force` is different: it resets each named theme directory wholesale, so files you added inside a bundled theme are removed.

**Granularity warning.** `copy-resources` is directory-granular and keeps **no backup**. `--js`
rewrites all four JS stubs (`synapps.js`, `synapse-components.js`, `synapse-rte.js`,
`sb-admin-2.min.js`) and `--css` rewrites `synapps.css` as well as `synapse-components.css` —
including files the stubs ship but you may have edited in place, and including ones you did not
have before (a project predating `synapse-rte.js` gains it). If you only want the component
files refreshed, and want the previous version kept as `.bak`, use
[`synapps:patch --force`](#synappspatch) instead.

---

## synapps:searchable-setup / synapps:searchable-forget

```bash
php artisan synapps:searchable-setup
php artisan synapps:searchable-forget {prefix} [--force]   # prefix must start with "content:"
```

`searchable-setup` enables the global backend search: creates the `searchables` table and (re)imports backend nav entries and module searchables. `searchable-forget` deletes content entries whose source starts with the prefix. See README → *Global Search*.

---

## synapps:reconfig

Interactive configuration updater for an already-set-up project. Re-prompts for the core `apps.json` fields (app code, name, author, domains, prefixes, title, description, multi-language) and rewrites `synapps/config/apps.json`.

```bash
php artisan synapps:reconfig

# Per-developer override: write only the answers you changed to apps.dev.json
php artisan synapps:reconfig --dev
```

With `--dev`, `apps.json` is left untouched: only the values that differ from the current effective config are merged into `synapps/config/apps.dev.json` (existing entries there are kept).

### What it does

1. Prompts for each core field, defaulting to the current value already in `config('synapps.apps.*')` so blank/unchanged answers don't erase existing data
   - Also prompts to enable/configure in-app notifications (same two questions as `synotif:setup` below) — only if `vm-engine/synapps-auth` is installed, otherwise this step is skipped
   - Also asks **"Enable the rich-text editor?"**, defaulting to the current setting. Yes adds `jodit` and `mews/purifier` to the `dependencies` block and the `synapse-rte.js` import to `resources/js/app.js`; switching it from off to on also runs `synapps:discover` to install them and rebuild assets. No removes the two packages from the `dependencies` block and the import from `app.js` (packages already installed in the host stay installed)
2. **Preserves, rather than resets, these blocks** — read from the current config and carried forward as-is unless you edit `apps.json` directly:
   - `themes` (active/fallback theme, per-layout CSS/JS/headers)
   - `dependencies` (npm/composer packages, including any added manually)
   - `csp` (Content-Security-Policy allowlist)
   - `logging` (API request-log sensitive keys / max body size)
3. Writes the merged result back to `synapps/config/apps.json`

### When to run

- Renaming the app, changing its domain(s), or switching prefixes after initial `synapps:setup`
- **Not required** for theme, dependency, CSP, or logging changes — those blocks pass through untouched; edit `synapps/config/apps.json` directly for those instead

### After running

```bash
php artisan synapps:config
```

> **Note:** Prior to v2.4.7, `themes`, `dependencies`, and `git_repo` were reset to hardcoded defaults on every run. Upgrade if you rely on `synapps:reconfig` and have customized any of these.

---

## synotif:setup

Enable/configure the in-app notification system (see [README.md#notifications](../README.md#notifications) for usage). Requires `vm-engine/synapps-auth` — aborts with an explanatory error if it isn't installed, since role-based targeting depends on it.

```bash
php artisan synotif:setup
```

### What it does

1. Prompts: enable notifications (default from current config), and a log channel for skipped/disabled notifications (blank = silent)
2. If enabling and the `notifications` table doesn't exist yet: publishes the `notifications:table` migration (skipped if a matching migration file already exists) and runs `migrate`
3. Writes an `notifications` block (`enabled`, `log_channel`) into `synapps/config/apps.json`, preserving everything else
4. Runs `synapps:config` to recompile

### When to run

- Turning notifications on (or off, or changing the log channel) after initial `synapps:setup`
- `synapps:setup` and `synapps:reconfig` ask the same two questions inline, so this command is mainly for toggling the feature standalone after the fact

---

## synapps:vendor-fonts

Self-hosts a theme's Google Fonts so the app makes no third-party font CDN calls (useful for CSP hardening or offline/air-gapped deployments).

```bash
php artisan synapps:vendor-fonts {theme?}
```

### Arguments

- `theme` (optional) - Theme to vendor fonts for; defaults to the active theme (`config('synapps.apps.themes.active')`)

### What it does

1. Scans the theme's `theme.json` `headers.*` layouts for a `fonts.googleapis.com/css2` `<link>` tag
2. Downloads the Google Fonts CSS, then downloads every referenced `.woff2` file from `fonts.gstatic.com`
3. Rewrites the CSS to reference the local files and saves it to `synapps/resources/themes/{theme}/assets/fonts/fonts.css`
4. Removes the Google Fonts `<link>`/`preconnect` entries from `theme.json`'s `headers.*` and registers the local `fonts.css` in `theme.json`'s `css.*` for each affected layout
5. Nothing is written to disk until every font file has downloaded successfully — a failed download leaves the theme untouched

### Examples

```bash
# Vendor fonts for the active theme
php artisan synapps:vendor-fonts

# Vendor fonts for a specific theme
php artisan synapps:vendor-fonts my-theme
```

### After running

```bash
npm run build          # copy the new font files into public/
php artisan synapps:config   # recompile theme.json changes
```

### When to run

- Tightening `font-src` in the [`csp` config block](../docs/CONFIGURATION.md#csp-configuration) to drop `fonts.googleapis.com`/`fonts.gstatic.com`
- Avoiding external requests on every page load for privacy/performance/compliance reasons

---

## synapps:publish-settings

Publishes the Settings module to your `synapps/modules/` directory.

```bash
php artisan synapps:publish-settings
```

### What it does

- Copies the Settings module stub from `vendor/vm-engine/synapse/stubs/modules/settings/` to `synapps/modules/settings/`
- Provides a complete settings management interface for your application
- Integrates with DbConfig and DbLang services

### Requirements

- **SynAuth package** must be installed (`vm-engine/synapps-auth`)
- The command will fail with an error if SynAuth is not available

### When to run

- After installing Synapse and SynAuth
- When you need a settings management interface
- Can be run multiple times (overwrites existing settings module)

### After Publishing

1. Run `php artisan synapps:discover` to register the new module
2. Run `php artisan migrate` if the module includes migrations
3. Access the settings interface under the backend prefix (e.g. `/apps/settings` with the default `/apps` `backend_prefix`)

---

## synapse:db-config

Create or update database configuration values.

```bash
php artisan synapse:db-config {key} {value?} --label=
```

### Arguments

- `key` - Configuration key in format `group.key` (e.g., `app.maintenance_mode`)
- `value` - Configuration value (optional for display)

### Options

- `--label` - Optional label for documentation

### Examples

```bash
# Set maintenance mode
php artisan synapse:db-config app.maintenance_mode true --label="Maintenance Mode"

# Set site name
php artisan synapse:db-config app.site_name "My Application"

# Set numeric value
php artisan synapse:db-config app.max_users 100 --label="Maximum Users"
```

### When to run

- When you need to update application configuration
- During deployment to set environment-specific configs
- For runtime configuration management

---

## synapse:db-lang

Create or update database translation values.

```bash
php artisan synapse:db-lang {key} {value?} --label= --language=
```

### Arguments

- `key` - Translation key (e.g., `welcome.title`)
- `value` - Translation value (optional for display)

### Options

- `--label` - Optional label for documentation
- `--language` - Language code (default: `en`)

### Examples

```bash
# Set English translation
php artisan synapse:db-lang welcome.title "Welcome to our site" --label="Welcome Title"

# Set Indonesian translation
php artisan synapse:db-lang welcome.title "Selamat datang" --label="Welcome Title" --language=id

# Set Spanish translation
php artisan synapse:db-lang menu.home "Inicio" --language=es

# Update existing translation
php artisan synapse:db-lang footer.copyright "© 2025 My Company" --language=en
```

### When to run

- When adding new translatable strings
- During deployment to set language-specific content
- For runtime translation management

---

## synapse:excel-cleanup

Mark stale Excel import/export tasks as failed. Tasks are considered stale when they remain in `processing` status but their heartbeat has not been updated within the timeout period.

```bash
php artisan synapse:excel-cleanup {--timeout=3900}
```

### Options

- `--timeout=3900` - Seconds after which a processing task is considered stale (default: 3900 = job timeout + 5 min buffer)

### What it does

1. Scans `excel_imports` table for tasks with `processing` status and stale heartbeat
2. Scans `excel_exports` table for tasks with `processing` status and stale heartbeat
3. Marks stale tasks as `failed` with error message "Marked as failed by cleanup command (stale heartbeat)"
4. Reports how many tasks were cleaned up

### When to run

- On a schedule to automatically recover from crashed/killed job processes:
  ```php
  // In your app's console.php or scheduler
  Schedule::command('synapse:excel-cleanup')->everyFiveMinutes();
  ```
- Manually when you suspect tasks are stuck in `processing` status
- After server crashes or OOM kills that terminated running jobs

### How stale detection works

Background import/export jobs update a `last_heartbeat_at` timestamp after each chunk. If a process is killed (OOM, crash, manual kill), the heartbeat stops updating. This command identifies those tasks and marks them as failed so they can be resumed.

### Related

- `ExcelImporter::resume($task)` - Resume a failed import from the last checkpoint
- `ExcelExporter::resume($task)` - Resume a failed export (re-runs from scratch)
- See [Excel Import/Export Documentation](EXCEL.md) for complete details

---

## synapps:upgrade-audit

Detect legacy `vm-engine/synapse` patterns that need updating, and optionally auto-fix the ones that are mechanical. By default this command only reports — pass `--apply` to rewrite files.

```bash
php artisan synapps:upgrade-audit
php artisan synapps:upgrade-audit --apply
```

### Options

- `--apply` - Rewrite the auto-fixable findings instead of only reporting
- `--only=*` - Restrict to specific rule IDs, e.g. `--only=A --only=B`
- `--force` - Skip the git-clean check and confirmation prompt (only relevant with `--apply`)
- `--output=SYNAPSE_UPGRADE_AUDIT.md` - Path to write the markdown report, relative to the project root

### What it does

1. Scans the project (excluding `vendor/`, `node_modules/`, `storage/`, `bootstrap/`, `.git/`) for known breaking-change patterns from past Synapse major/minor versions.
2. Prints a console summary and writes a markdown report to `SYNAPSE_UPGRADE_AUDIT.md` (or `--output`).
3. Without `--apply`: stops here — zero writes besides the report.
4. With `--apply`: checks the working tree is clean (unless `--force`), confirms with you (unless `--force`), writes a `{file}.bak` backup per file, then rewrites the auto-fixable findings.

Findings fall into two groups:

- **Auto-fixable** — mechanical, unambiguous rewrites (Blade `<livewire:module.name>` → `<livewire:module::name>`, `Route::livewire()` dotted string targets, removed `registerMfcComponents()`/`registerLivewireComponents()` calls).
- **Manual review required** — flagged but never rewritten, since the fix isn't mechanical (Livewire v3 `Livewire.hook()` JS calls needing a port to `Livewire.interceptMessage()`, `Route::get(Component::class)` calls that may or may not be Livewire full-page routes, `@themeScripts(...)` rendered after `</head>` in a layout — move it into `<head>` right after `@themeStyles(...)`, see [THEMES.md](THEMES.md#themescripts) — and Alpine components registered only in an anonymous `alpine:init` callback, which never runs after a cross-layout `wire:navigate`).

### Before you're on a version that ships this command

If your project is still on an old `vm-engine/synapse` version that doesn't ship this command yet, copy the self-contained standalone script `vendor/vm-engine/synapse/tools/synapse-upgrade-audit.php` into your project root and run it directly — no Composer install/update required:

```bash
php synapse-upgrade-audit.php --path=. --output=SYNAPSE_UPGRADE_AUDIT.md
```

It implements the same detection rules and produces the same report format, but is audit-only (no `--apply` equivalent) since it has no way to know it's safe to modify files in a project it isn't installed into yet. See [UPGRADE_GUIDE.md](../UPGRADE_GUIDE.md) for the recommended before-you-upgrade workflow.

### When to run

- Before upgrading `vm-engine/synapse` to a newer minor/major version, to see what will need attention
- After upgrading, to confirm no legacy patterns remain
- Periodically on older projects that haven't kept up with Synapse releases

### See Also

- [UPGRADE_GUIDE.md](../UPGRADE_GUIDE.md) - Full upgrade process and version-specific breaking change guides
- [CHANGELOG.md](../CHANGELOG.md) - Detailed history of every change per version

---

## Additional Commands

### Publish Configuration

Publish Synapse configuration files manually:

```bash
php artisan vendor:publish --tag=synapse-config
```

This publishes:
- `config/synapse.php`
- `config/synapps.php`

### Clear Configuration Cache

```bash
php artisan config:clear
```

After clearing cache, run `synapps:config` to regenerate Synapse configurations.
