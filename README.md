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
        "vm-engine/synapse": "^3.3"
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

- PHP 8.5+
- Laravel 13.x
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

# Generate module components (unified command)
php artisan synapps:make module blog --author="Your Name"
php artisan synapps:make model blog Post -mfs
php artisan synapps:make controller blog PostController --resource
php artisan synapps:make livewire blog PostList
# ...or the individual synapps:make-{type} commands still work the same way

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
<x-synapse-datepicker mode="month" wire:model.live="period" />   {{-- year/month grid, submits Y-m like <input type="month"> --}}
<x-synapse-currency currency="$" />
<x-synapse-currency currency="Rp" raw :decimals="2" wire:model="amount" />   {{-- shows 1.250.000,50, sends 1250000.50 --}}
<x-synapse-search-box placeholder="Search..." wire:model.live.debounce="q" />
<x-synapse-field name="amount" label="Amount" type="number" hint="..." required />   {{-- label + input + error/hint, aria-linked; slot replaces the input --}}
<x-synapse-field name="notes" label="Notes" type="textarea" rows="4" input-class="text-right" class="sm:col-span-2" />   {{-- inputClass styles the control, class the form-box --}}
<x-synapse-adv-select wire-model="status" :options="$statusOptions" />   {{-- options: [["value" => .., "label" => ..]] --}}
<x-synapse-toggler label="Active" wire:model.live="isActive" />
<x-synapse-color-picker wire:model="color" :swatches="['#ef4444', '#3b82f6']" />   {{-- #rrggbb, validate with hex_color --}}

<!-- File upload -->
<x-synapse-drag-n-drop wireModel="avatar" />          {{-- single image, with preview --}}
<x-synapse-file-drop wire-model="docs" multiple       {{-- multi-file, any type --}}
    :max-count="3" :allowed-types="['pdf','jpg']" />

<!-- Layout components -->
<x-synapse-panel title="Clients">...</x-synapse-panel>
<x-synapse-stat-tile icon="ph ph-invoice" color="blue" label="Total Invoices" value="2,613" />
<x-synapse-badge color="success">Active</x-synapse-badge>

<!-- List helpers -->
<x-synapse-sort-icon field="name" :sort-field="$sortField" :sort-direction="$sortDirection" />
<x-synapse-per-page-selector :paginator="$items" />   {{-- binds $perPage, 10/15/25/50 --}}
<x-synapse-excel-export-button mode="sync" action="exportToExcel" label="Export" exporting-label="Exporting…" />

<!-- UI components -->
<x-synapse-confirm-dialog title="Delete?" message="Are you sure?" />
<x-synapse-drawer title="Settings">...</x-synapse-drawer>
<x-synapse-modal name="edit" title="Edit">... <x-slot:footer>...</x-slot:footer></x-synapse-modal>     {{-- $dispatch('open-modal-edit') / 'close-modal-edit' --}}
<x-synapse-lightbox name="preview" />                {{-- open via $dispatch('open-lightbox-preview', { url }) --}}
<x-synapse-toastr />
<x-synapse-empty-state icon="ph ph-users" title="No members yet">…action…</x-synapse-empty-state>
<x-synapse-copy-button text="..." label="Copy path" />   {{-- clipboard + execCommand fallback, "Copied" feedback --}}

<!-- Planning & layout -->
<x-synapse-steps :steps="['Details', 'Media', 'Review']" :current="$step" go-method="goToStep" />
<x-synapse-split-pane aside-width="20rem" resizable storage-key="nodes"><x-slot:aside>...</x-slot:aside>...</x-synapse-split-pane>
<x-synapse-kanban sort-method="moveItem">                {{-- moveItem($id, $position, $columnId), native wire:sort --}}
    <x-synapse-kanban.column id="draft" label="Draft" color="gray">
        <x-synapse-kanban.card :id="$item->id">...</x-synapse-kanban.card>
    </x-synapse-kanban.column>
</x-synapse-kanban>
<x-synapse-calendar :month="$month" :events="$events" nav-method="setMonth" event-method="openEvent" day-method="createOn" />
```

### Icons

Synapse-provided components (badge, panel, search-box, confirm-dialog, ...) accept a plain icon *class string*, so any icon font works — pick one and pass its class:

```blade
<x-synapse-badge color="success" icon="ph ph-check">Verified</x-synapse-badge>
```

**Phosphor Icons** (`@phosphor-icons/web`, loaded from `node_modules` — no CDN) is the recommended set: 9,000+ icons, and — unlike FontAwesome Free — every icon has a real glyph in every weight it ships. Classes look like `ph ph-<name>` (e.g. `ph ph-house`, `ph ph-trash`, `ph ph-check-circle`). Only the `regular` weight is imported by default (`@import "@phosphor-icons/web/regular";` in `synapps.css`) to keep the CSS bundle small; to add another weight (`bold`, `fill`, `duotone`, `light`, `thin`), add its import next to it and use the matching class prefix documented at [phosphoricons.com](https://phosphoricons.com).

**FontAwesome Free** (`@fortawesome/fontawesome-free`) remains installed and available as an alternative — classes look like `fa-solid fa-<name>`. Watch out: FA Free ships one font file per style (solid/regular/brands), and **most icons only have a glyph in the solid font** — `fa-regular`/`fa-light`/`fa-duotone` render nothing for the vast majority of icons in the free tier (those weights are Pro-only per-icon). Stick to `fa-solid` unless you've confirmed the specific icon has a free non-solid glyph.

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

## Rich-text editor (optional)

`<x-synapse-rte field="body" :value="$body" />` is a Jodit editor bound to a Livewire property, with `VmEngine\Synapse\Rte\RichText::clean()` to sanitise what it submits. It is off by default: answer yes to "Enable the rich-text editor?" in `synapps:setup` or `synapps:reconfig`, which adds `jodit` and `mews/purifier` to the project. While it is off the tag shows a notice above a plain textarea, so forms keep working. Modules can add toolbar buttons with `window.synapseRte.extend()`. See [docs/COMPONENTS.md](docs/COMPONENTS.md#rich-text-editor-optional).

## Menu badges

A backend menu item can show a count (pending items, unread, to review). Name a badge class on the item in the module's `module.json`, then run `php artisan synapps:config`:

```json
"pending": { "id": "pending", "name": "__mod::menu.be.pending", "route": "mod.pending.index", "acl": "mod.pending",
             "badge": "Vendor\\Module\\Nav\\PendingBadge" }
```

```php
use VmEngine\Synapse\Nav\NavBadge;

final class PendingBadge extends NavBadge
{
    public function count(): ?int
    {
        return Task::query()->where('status', 'pending')->count();
    }

    public static function refreshOn(): array
    {
        return [Task::class];   // recount when a Task is saved or deleted
    }
}
```

| Method | Default | Meaning |
|---|---|---|
| `count()` | required | The number. `null` or `0` shows nothing; above 99 shows `99+`. |
| `refreshOn()` | `[]` | What makes the cached count stale: Eloquent model classes (any save or delete) and/or event classes (when fired). |
| `ttl()` | `3600` | Seconds the count may stay cached when nothing refreshes it first. `0` = never cache, `null` = keep until refreshed. |
| `scope()` | `''` | Cache variant for counts that differ per viewer, e.g. `(string) auth()->id()`. |

Refresh by hand where events do not reach — bulk `update()`/`delete()` queries fire no model events:

```php
synav()->refreshBadge(PendingBadge::class);            // for everyone
synav()->refreshBadge(MyTasksBadge::class, $userId);   // one scope only
synav()->refreshBadges();                              // every badge
```

- A parent item shows the sum of the children the current user may see.
- The class is resolved from the container. Keep `count()` to a single fast query.
- A badge class that is missing or throws is skipped and logged; it never breaks the menu.
- Counts use the project's cache store. With the `array` store they are recounted on every request.
- Backend menu only.

**Existing projects:** the badge is rendered by `<x-synapse-nav-badge :item="$item" />` in the host's `menu-item` and `submenu-item` nav views. Run `php artisan synapps:patch layouts` to add that line (safe to re-run; a customised view is reported and left alone).

## Global Search

Opt-in backend command palette (Ctrl/⌘+K) over pages and module content.

### Enable

```bash
php artisan synapps:searchable-setup
```

Creates the `searchables` table and imports every routed backend nav item plus each module's `searchables` block, once per enabled language. Re-run after installing/updating a module or adding a language. Content rows are never touched. Until setup runs, the whole API is a silent no-op and the palette renders nothing.

Existing apps: add `<livewire:synsearch::palette />` to `synapps/resources/views/components/layouts/partials/header.blade.php` (next to the notification bell) and copy the `.synsearch-*` rules from the stub `synapse-components.css`.

### Extra pages via `module.json`

```json
"searchables": {
    "create": {
        "name": "__example::search.create",
        "description": "__example::search.create_desc",
        "route": "example.create",
        "icon": "ph ph-plus",
        "acl": "example.manage",
        "keywords": "new add"
    }
}
```

Needs `name` and `route` (backend-relative, like nav) or `url`. Run `synapps:config` then `synapps:searchable-setup`.

### Content from code

```php
use VmEngine\Synapse\Facades\SynSearchable;

// on save — plain string = all locales; ['en' => ..., 'id' => ...] = per locale
SynSearchable::put(
    source: 'content:example.item',
    key: (string) $item->id,
    title: $item->name,
    description: $item->summary,
    route: 'example.edit',
    routeParams: ['id' => $item->id],
    icon: 'ph ph-box',
    acl: 'example.manage',
    keywords: $item->code,
);

// on delete
SynSearchable::forget('content:example.item', (string) $item->id);

// bulk (prefix match), e.g. module uninstall
SynSearchable::forgetSource('content:example');

// query directly (ACL + locale filtered)
synsearchable()->search('acme', limit: 10);
```

Sources must start with `content:`; `nav` and `module:*` belong to setup.

```bash
php artisan synapps:searchable-forget content:example   # asks first; --force to skip
```

### Engine

Default is Eloquent `LIKE`. To use Laravel Scout (Typesense, Meilisearch, …) install and configure Scout, then set in `synapps/config/apps.json`:

```json
"searchable": { "engine": "scout" }
```

If Scout isn't installed, or the engine throws, search falls back to Eloquent.

## Support

For issues and questions, please contact the VM Engine development team or create an issue in the repository.

## License

This package is proprietary software developed by VM Engine.

---

**[📖 View Full Documentation](docs/)**
