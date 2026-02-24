# Artisan Commands

Synapse provides several Artisan commands for managing your modular application.

## Quick Reference

| Command | Purpose | When to Run | Idempotent | Auto-run by Setup |
|---------|---------|-------------|------------|-------------------|
| `synapps:setup` | Initial setup wizard | Once on fresh install | No (overwrites) | N/A |
| `synapps:config` | Regenerate config files | After config changes | Yes | ✅ Yes |
| `synapps:discover` | Module discovery & deps | After module changes | Yes | ✅ Yes |
| `synapps:patch-resources` | Patch JS/CSS/Vite/routes/seeder | After install, if assets missing | Yes | ✅ Yes |
| `synapps:patch-tests` | Configure test discovery | After install, new modules | Yes | ✅ Yes |
| `synapps:link-errors` | Link error views | After custom error pages | Yes | ✅ Yes |
| `synapps:copy-stubs` | Copy stub modules into synapps/modules | When you need example module code | Yes | No |
| `synapps:make-theme` | Create new theme from template | When creating custom themes | Yes | No |
| `synapps:publish-settings` | Publish Settings module | When you need settings module | Yes | No |
| `synapse:db-config` | Manage database configuration | When updating configs | Yes | No |
| `synapse:db-lang` | Manage database translations | When updating translations | Yes | No |
| `synapse:excel-cleanup` | Mark stale Excel tasks as failed | Scheduled or manual | Yes | No |

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
   - `synapps:patch-resources` - Patches JS/CSS imports
   - `synapps:patch-tests` - Configures testing infrastructure
   - `synapps:discover` - Discovers modules and updates dependencies

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
3. Reads source configs from `synapps/config/{apps,system,modules}.json` and `.dev.json` overrides
4. Executes all config builders (Apps, System, Modules, Binding) through their lifecycle phases
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
4. Smart merges dependencies using semver (only upgrades versions)
5. Runs `composer dump-autoload` or `composer update` as needed
6. Runs `npm install` if package.json changed

### When to run

- After creating new modules
- After modifying `module.json` files
- After changing module dependencies
- **Automatically called by `synapps:setup`**

---

## synapps:patch-resources

Integrates Synapse resources into your Laravel application and cleans up default Laravel files.

```bash
php artisan synapps:patch-resources
```

### What it does

- Adds `import '../../synapps/resources/js/synapps.js';` to `resources/js/app.js`
- Adds `@import '../../synapps/resources/css/synapps.css';` to `resources/css/app.css`
- Adds Tailwind CSS `@source` directives for module Blade/JS files
- Configures Vite to copy static assets from modules
- Comments out default Laravel welcome route in `routes/web.php`
- Comments out default user seeder in `database/seeders/DatabaseSeeder.php`
- Adds global `window.withBack()` JavaScript helper to `resources/js/app.js`

### Backup files

Before modifying any file, the command creates a `.bak` backup (e.g. `app.js.bak`). The backup is only created once — subsequent runs will not overwrite it.

### Patched file reporting

Each successfully patched file is reported individually:

```
Patched: app.js — synapps import
Patched: app.css — synapps import
Patched: vite.config.js
Patched: app.js — global helpers
```

### Safe to run multiple times

Only patches if changes are not already present.

### When to run

- After fresh installation
- If Synapse assets are not loading
- **Automatically called by `synapps:setup`**

---

## synapps:patch-tests

Configures comprehensive testing infrastructure for Synapse modules.

```bash
php artisan synapps:patch-tests
```

### What it does - patches 4 files

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

### Run test suites

```bash
php artisan test                        # All tests
php artisan test --testsuite=Browser    # Browser tests only
php artisan test --testsuite=Modules    # Module tests only
php artisan test --testsuite=Packages   # Package tests only
```

### Backup files

Before modifying `tests/Pest.php` and `.env.testing`, the command creates `.bak` backups. The backup is only created once — subsequent runs will not overwrite it.

### When to run

- After fresh installation
- After adding new modules with tests
- **Automatically called by `synapps:setup`**

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
2. **Customize styles**: `synapps/resources/themes/{name}/css/theme.css`
3. **Update layouts**: `synapps/resources/themes/{name}/layouts/*.blade.php`
4. **Add Alpine.js components**: `synapps/resources/themes/{name}/js/theme.js`
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
│   └── theme.css          # Custom Tailwind v4 styles
├── js/
│   └── theme.js           # Alpine.js components and utilities
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
3. Access the settings interface (usually at `/admin/settings` if using backend routes)

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
