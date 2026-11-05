# Blade Components

Synapse provides pre-built Blade components for common UI patterns. All components use the `synapse::` namespace.

## Component Text and Translations

The text these components emit themselves — `Close`, `Clear selection`, `No results found.`, `Per page`, the notifications panel, the calendar, the search palette — **is already translated into Indonesian by the package**, in `lang/id.json`, loaded through `loadJsonTranslationsFrom()` in `SynapseServiceProvider::register()`.

Do not re-declare those keys in your app. A host app's `lang/{locale}.json` takes precedence over a package's, so an app-level copy silently overrides the package's wording — with no error, and no sign anything is being shadowed until someone diffs the two files. One project had reimplemented 20 of these keys and was quietly replacing two of them with different wording. Check `vendor/vm-engine/synapse/lang/id.json` before adding any key of your own.

Add an app-level entry only to *deliberately* reword a string. If a key is missing or wrong in the package, fix it there — `tests/Unit/TranslationsTest.php` fails if any bare `__('…')` key the package emits has no `id.json` entry, so a gap is a package bug, not something for every app to patch around.

Strings you pass in as props (`title`, `label`, `placeholder`, …) are yours to translate; only the components' built-in text comes from the package.

## Form Components

### Checkboxes

Multiple checkbox inputs for array-based selections:

```blade
<x-synapse-checkboxes
    name="permissions"
    :options="['read' => 'Read', 'write' => 'Write', 'delete' => 'Delete']"
    :model="$selectedPermissions" />
```

**Props:**
- `name` (string, required) - Input name
- `options` (array, required) - Available options
- `model` (array, default: []) - Selected values

---

### Radio Buttons

Radio button group for single selection:

```blade
<x-synapse-radio-buttons
    name="status"
    :options="['active' => 'Active', 'inactive' => 'Inactive']"
    :model="$currentStatus" />
```

**Props:**
- `name` (string, required) - Input name
- `options` (array, required) - Available options
- `model` (string, default: '') - Selected value

---

> **`<x-synapse-select>` and `<x-synapse-multiselect>` were removed in v3.0.0.** Use [Advanced Select](#advanced-select) (`<x-adv-select>`) instead — it covers both single and multiple selection, plus searchable/remote data. See [UPGRADE.md](../UPGRADE.md#upgrading-to-v300) for the migration shape.

### Password Input

Password field with show/hide toggle:

```blade
<x-synapse-password />
```

---

### Password Generator

Read-only password generator with separate generate and apply actions:

```blade
{{-- Single property --}}
<x-synapse::password-generator
    model="password"
    :length="12"
/>

{{-- Multiple properties (e.g., password + confirmation) --}}
<x-synapse::password-generator
    :model="['password', 'password_confirmation']"
    :length="16"
/>

{{-- Display only (no apply button) --}}
<x-synapse::password-generator :length="20" />
```

**Props:**
- `model` (string|array, optional) - Livewire property name(s) to set when Apply button is clicked. Accepts single string or array of strings
- `length` (int, default: 12) - Password length (minimum: 8)
- `...attributes` - Any additional HTML input attributes

**Features:**
- **Read-only field**: Password is always visible, cannot be manually edited
- **Separate actions**: Generate creates password, Apply sets Livewire properties
- Generate secure passwords with guaranteed character variety (lowercase, uppercase, numbers, symbols)
- Copy generated password to clipboard
- Set multiple Livewire properties at once (perfect for password + confirmation fields)
- Generate multiple times before applying
- Alpine.js powered with no wire:model dependency

**Buttons:**
- **Generate** (refresh icon) - Creates new password and displays in field
- **Apply** (checkmark icon) - Sets current password to Livewire properties (only shown if `model` is provided)
- **Copy** (copy icon) - Copies password to clipboard

**Events:**
- `password-set` - Dispatched when Apply button is clicked
- `password-copied` - Dispatched when Copy button is clicked

**See:** [Complete Usage Examples](COMPONENTS_PASSWORD_GENERATOR_EXAMPLE.md)

---

### Currency Input

Currency input with formatting:

```blade
<x-synapse-currency currency="$" />
<x-synapse-currency currency="Rp." />

{{-- Shows "1.250.000,50" while typing; $amount receives "1250000.50" --}}
<x-synapse-currency currency="Rp" raw :decimals="2" wire:model="amount" />

{{-- Same, with label, error and hint wiring --}}
<x-synapse-field name="amount" label="Amount" type="currency" prefix="Rp" :decimals="2" required />
```

**Props:**
- `currency` (string, default: 'Rp.') - Currency symbol
- `decimals` (int, default: 0) - Decimal places the mask allows (',' decimal, '.' thousands)
- `raw` (bool, default: false) - `wire:model` receives the plain number (`"1250000.50"`, `""` when empty) instead of the formatted text (`"1.250.000,50"`). Use it for decimal amounts so actions can validate with `numeric` and need no parsing. Requires the `synapseCurrency` component from `synapse-components.js`.

Without `raw` the component keeps sending the formatted text, as before.

---

### Datepicker

Date picker using Flatpickr:

```blade
<x-synapse-datepicker />

<!-- Month/year only — replaces <input type="month"> -->
<x-synapse-datepicker mode="month" wire:model.live="period" />

<!-- With custom Flatpickr config -->
<x-synapse-datepicker
    :flatpickr='{"dateFormat": "Y-m-d", "enableTime": true}' />

<!-- Date + time, and time only (verified round-tripping through wire:model) -->
<x-synapse-datepicker
    :flatpickr="json_encode(['enableTime' => true, 'dateFormat' => 'Y-m-d H:i', 'time_24hr' => true])" />
<x-synapse-datepicker
    :flatpickr="json_encode(['noCalendar' => true, 'enableTime' => true, 'dateFormat' => 'H:i'])" />
```

**Props:**
- `flatpickr` (string, default: '{}') - JSON configuration for Flatpickr
- `mode` (string, default: 'date') - `'month'` renders a year/month grid (Flatpickr's `monthSelect`
  plugin) and formats as `Y-m`, matching what `<input type="month">` submits, so an existing
  `wire:model` value and `date_format:Y-m` validation need no change. Any other value is treated as
  `'date'`. A `dateFormat` in `flatpickr` still wins and applies to both the grid and the input text.

`mode="month"` needs the Alpine side of the package in the host app, and
`synapps/resources/js/synapse-components.js` is only *created* by `synapps:patch`, never updated by it.
Refresh it with `php artisan synapps:patch --force` (that file and `synapse-components.css` only, previous
version kept as `.bak`), then `npm run build`. `synapps:copy-resources --js --force` also replaces it, but
copies the whole stub directory and keeps no backup — see docs/COMMANDS.md.

All other attributes (`wire:model`, `id`, `class`, …) pass through to the `<input>`. Inside an `<x-synapse-field>` the input also picks up the field's error/hint description and invalid/required state through `@aware`, so the call site writes no aria.

---

### Field

Label, wire-bound input and its error/hint in a `form-box`, linked for screen readers (`for`, `aria-invalid`, `aria-describedby`).

```blade
<x-synapse-field name="amount" label="Amount" type="number" inputmode="decimal" hint="Minimum 10,000" required />

{{-- Multi-line --}}
<x-synapse-field name="notes" label="Notes" type="textarea" rows="4" />

{{-- Any other control: put it in the slot and give it the field id --}}
<x-synapse-field name="form.group_id" label="Group">
    <select class="form-input @error('form.group_id') has-error @enderror" id="f-form-group_id" wire:model="form.group_id">…</select>
</x-synapse-field>

{{-- adv-select and datepicker take the aria wiring from the field themselves,
     including the label's id (a div is not labelable, so `for` cannot name it) --}}
<x-synapse-field name="clientId" label="Client" hint="Billing client">
    <x-synapse-adv-select wire-model="clientId" :options="$clients" />
</x-synapse-field>
```

**Slotted controls and aria.** The built-in branches wire `aria-describedby` (error and hint ids),
`aria-invalid` and `aria-required` onto their own control. A slotted `<x-synapse-adv-select>` or
`<x-synapse-datepicker>` gets the same wiring with nothing at the call site — both read the field's
`$fieldAria` through `@aware`, and anything you pass explicitly still wins. Raw HTML in a slot cannot be
reached by Blade, so write it there yourself:

```blade
<x-synapse-field name="code" label="Code" hint="Generated">
    <input class="form-input @error('code') has-error @enderror" id="f-code" type="text"
        aria-describedby="f-code-hint" @error('code') aria-invalid="true" aria-describedby="f-code-error f-code-hint" @enderror
        wire:model="code">
</x-synapse-field>
```

| Prop | Default | Description |
|---|---|---|
| `name` | — | `wire:model` path, also the validation error key unless `error` is given |
| `error` | `name` | Validation error key when the form validates under another key, e.g. `name="profile.phone" error="phone"` |
| `prefix` | — | Text before the input in an input group, e.g. `Rp` |
| `label` | — | Visible label |
| `type` | `text` | Input type; `textarea` renders a `<textarea>`, `currency` a raw currency input |
| `rows` | `3` | Rows of a `type="textarea"` field |
| `inputClass` | — | Classes for the control itself, e.g. `text-right` (`class` styles the form-box instead) |
| `required` / `disabled` | `false` | Marks the box required (`aria-required`, no native `required`, so the server-side message shows) / disables the input |
| `hint` | — | Helper text under the input |
| `step`, `inputmode`, `autocomplete` | — | Passed to the input |
| `modifier` | — | `wire:model` modifier, e.g. `live` or `blur` |
| `id` | `f-{name}` (dots → dashes) | Input id |

Other attributes go to the input; `class` is merged into the `form-box` (so a grid span like `sm:col-span-2` works), and the label carries `id="{id}-label"` for `aria-labelledby`.

### Search Box

Text input with a fixed left search icon and an optional trailing slot:

```blade
<x-synapse-search-box
    :placeholder="__('pm::client.search_placeholder')"
    wire:model.live.debounce="q"
/>

{{-- With a trailing element, e.g. a filter-drawer toggle --}}
<x-synapse-search-box placeholder="Search..." wire:model.live.debounce="q">
    <x-slot:trailing>
        <button type="button" class="btn" @click="drawerOpen = true">
            <span class="ph ph-funnel"></span>
        </button>
    </x-slot:trailing>
</x-synapse-search-box>
```

**Props:**
- `placeholder` (string, optional) - Input placeholder (no hardcoded default — pass a translated string)
- All other attributes (`wire:model`, `id`, `name`, ...) pass through to the `<input>`

**Slots:**
- `trailing` (optional) - Element appended after the input

---

### Color Picker

Native color input + hex field + optional preset swatches, entangled to `wire:model`.

```blade
<x-synapse-color-picker wire:model="form.color" :swatches="['#ef4444', '#f59e0b', '#10b981', '#3b82f6']" />
<x-synapse-color-picker wire:model="form.color" :swatches="$palette" :allow-custom="false" />  {{-- swatches only --}}
```

| Prop | Type | Default | Notes |
|---|---|---|---|
| `swatches` | array | `[]` | `#rrggbb` presets; invalid entries are dropped |
| `allowCustom` | bool | `true` | Native picker + hex field; forced on when there are no swatches |

Value is `#rrggbb`. Validate with Laravel's `hex_color` rule.

## Rich-Text Editor (optional)

`<x-synapse-rte>` is a [Jodit](https://xdsoft.net/jodit/) editor bound to a Livewire property. It is an optional feature, off by default, because it needs two extra packages: `jodit` (npm) and `mews/purifier` (composer).

**Enable it** by answering yes to "Enable the rich-text editor?" in `php artisan synapps:setup` or `php artisan synapps:reconfig`. That sets `rte.enabled` in `synapps/config/apps.json`, adds the two packages to its `dependencies` block (installed by `synapps:discover`), and imports `synapps/resources/js/synapse-rte.js` into `resources/js/app.js`.

```blade
<x-synapse-rte field="body" :value="$body" />

{{-- A Livewire form object, a taller editor and a shorter toolbar --}}
<x-synapse-rte field="form.content" :value="$form->content" :height="500" :buttons="['bold', 'italic', '|', 'ul', 'ol', '|', 'link']" />
```

**Props:**
- `field` (string, required) — the Livewire property to bind, e.g. `body` or `form.body`. Changes are deferred, like `wire:model`: they go to the server with the next request.
- `value` (string|null) — the initial HTML.
- `height` (int, default: `360`) — editor height in pixels.
- `buttons` (array|null) — Jodit toolbar button names. Default: undo, redo, paragraph, bold, italic, underline, strikethrough, left, center, right, ul, ol, link. A `source` button is always added at the end.
- `extension` (string|null) and `extension-config` (array) — name and config of a toolbar extension (see below).

**When the editor is disabled**, the tag shows the message "The rich-text editor is disabled. Enable it with `php artisan synapps:reconfig`." above a normal `<textarea>` bound to the same property with `wire:model`, so the form still works.

**Always clean the submitted HTML on the server:**

```php
use VmEngine\Synapse\Rte\RichText;

$this->body = RichText::clean($this->body); // null when nothing visible is left
```

`RichText::clean()` keeps basic formatting, headings, lists, links and images, and drops scripts, event handlers, inline styles and non-http(s)/mailto URLs. On a host without `mews/purifier` (the editor was never enabled) it cannot keep any HTML safely, so it stores the input as escaped plain text with line breaks kept. A module that needs a wider allowlist keeps its own purifier profile.

**Toolbar extensions.** A module adds its own buttons without Synapse knowing about them. Register the extension in the module's script, then name it on the tag:

```js
document.addEventListener('alpine:init', () => {
    if (!window.synapseRte) return; // the editor is disabled on this host

    // setup(rte, config) runs before the editor is built; rte.editor is set afterwards.
    window.synapseRte.extend('my-buttons', (rte, config) => ({
        buttons: [
            { name: 'stamp', icon: 'ok', tooltip: config.label, exec: () => rte.editor.s.insertHTML(config.text) },
        ],
        destroy() { /* remove any listeners added above */ },
    }));
});
```

```blade
<x-synapse-rte field="body" :value="$body" extension="my-buttons" :extension-config="['label' => 'Stamp', 'text' => 'Approved']" />
```

The editor's Alpine code lives in `synapse-rte.js`, not in `synapse-components.js`, because it imports Jodit and would break the asset build on a host that does not have it installed. It is loaded in the backend bundle (`resources/js/app.js`) only.

---

## File Upload Components

Synapse ships two complementary file-upload components. Pick by use case:

| | `<x-synapse-drag-n-drop>` | `<x-synapse-file-drop>` |
|---|---|---|
| Files | Single only | Single or multiple (`multiple` prop) |
| File types | Image-only with preview thumbnail | Any file type |
| Slot count | n/a | `maxCount` enforcement (combined with `currentCount`) |
| Default copy | English defaults via props | Hardcoded English **or** module-driven via `module="…"` lang namespace |
| Styling | Tailwind utilities baked into markup | Self-contained scoped CSS (`@once`-protected) |
| Best for | Avatar / logo / single-image fields | Multi-slot attachment forms (e.g. one drop zone per required document) |

### Drag-n-Drop (single image, with preview)

Single-file image picker with drag-and-drop, client-side validation, and a base64 preview thumbnail. Best paired with Livewire's `wire:model` for direct property binding.

```blade
<x-synapse-drag-n-drop
    wireModel="avatar"
    accept="image/png,image/jpeg,image/webp"
    :maxSize="2048"
    label="Drop your avatar here"
    description="PNG, JPG, or WebP up to 2 MB" />
```

**Props:**
- `name` (string, default: `'file'`) — `<input>` name attribute
- `wireModel` (string|null) — Livewire property to bind to
- `accept` (string, default: `'image/png,image/jpeg,image/webp'`) — comma-separated MIME types
- `maxSize` (int, default: `5120`) — max file size in **KB** (client-side validated)
- `maxWidth` / `maxHeight` (int|null) — server-side dimension validation hints
- `preview` (bool, default: `true`) — render image preview on selection
- `label`, `description`, `browseText` (string) — display copy
- `error` (string|null) — initial server-side error to display

**Behaviour:**
- Drag-and-drop and click-to-browse both supported.
- For `wireModel` mode: integrates with `livewire-upload-start` / `livewire-upload-finish` / `livewire-upload-error` events for upload progress feedback.
- Uses `x-cloak` to prevent FOUC on the preview block.

### File Drop (multi-file, any type)

General-purpose multi-file drop zone designed for forms with several "slots" (e.g. an activity submission form with one drop zone per required document type). Stays presentation-agnostic — no embedded image preview — so it works for PDFs, spreadsheets, etc.

```blade
{{-- Single file --}}
<x-synapse-file-drop
    wire-model="avatar"
    :allowed-types="['png', 'jpg', 'webp']"
    :max-size-kb="2048" />

{{-- Multiple files for one slot --}}
<x-synapse-file-drop
    wire-model="newFiles.{{ $doc->id }}"
    multiple
    :max-count="$doc->max_count"
    :max-size-kb="$doc->max_size_kb"
    :allowed-types="$doc->allowed_types"
    :current-count="$total"
    :error="$errors->first('newFiles.'.$doc->id)" />

{{-- With consumer-module translations --}}
<x-synapse-file-drop
    module="submission"
    wire-model="newFiles.5"
    multiple
    :max-count="3" />
```

**Props:**
- `wireModel` (string|null) — Livewire property; nested array paths supported (e.g. `newFiles.5`)
- `multiple` (bool, default: `false`) — accept multiple files in a single picker
- `maxCount` (int|null) — when set, the zone is replaced with a "Maximum file reached" pill once `currentCount >= maxCount`
- `maxSizeKb` (int|null) — shown in the auto-built hint and used by HTML5 client-side validation
- `allowedTypes` (array, default: `[]`) — file extensions **without** the leading dot (e.g. `['pdf', 'jpg']`); used to build both the `accept=".pdf,.jpg"` attribute and the hint text
- `currentCount` (int, default: `0`) — existing-plus-staged file count, supplied by the parent so the zone knows when it's full (existing files are rendered separately by the consumer)
- `name` (string, default: `'file'`) — input `name` attribute
- `error` (string|null) — server-side error string from `$errors->first(...)`
- `label`, `hint`, `fullText`, `uploadingText` (string|null) — explicit overrides for the four visible strings; if any is null the component falls back to either the module-namespaced translation or a hardcoded English default
- `module` (string|null) — translation namespace. When set, defaults resolve via `__("{module}::file_drop.{key}")` for `label`, `full`, `uploading`, `max`, `kb`. Any missing key falls back to the English default. Lets each consuming module localise without per-instance prop overrides.

**Behaviour:**
- Drag-and-drop: `dragover`/`dragenter` adds `.is-dragging` (brand teal background, solid border, 4px focus ring). On drop, files are written to the hidden input and a `change` event is dispatched so Livewire's `wire:model` picks them up.
- Self-contained styling: emits a `<style>` block via `@once` so the CSS lands once per page regardless of how many instances exist.
- Hint auto-built from `allowedTypes` + `maxSizeKb` (e.g. `"PDF, JPG · max 2,048 KB"`) when `hint` prop is null.
- Counter pill (`1/3 file`) renders when `maxCount` is set.
- Loading indicator: spinner + translated "Uploading…" text shown via `wire:loading wire:target="{wireModel}"`.

**Module translation file** (`lang/{locale}/file_drop.php`):
```php
return [
    'label' => 'Click to upload or drag &amp; drop',
    'full' => 'Maximum file reached',
    'uploading' => 'Uploading…',
    'max' => 'max',
    'kb' => 'KB',
];
```

The label supports inline HTML — `<em>` is styled by the component as a brand-teal underlined call-to-action, so a translation like `'<em>Click to upload</em> or drag &amp; drop'` renders the verb as a tactile target.

**Existing files:** the component owns the drop zone only. Render existing/already-uploaded files yourself above the component (the consumer keeps full control over the file-list markup, remove buttons, download links, etc.) and pass the count via `currentCount` so the component knows when the slot is full.

---

## UI Components

### Badge

Pill-shaped status/label badge with color variants and an optional icon:

```blade
<x-synapse-badge color="success">Active</x-synapse-badge>
<x-synapse-badge color="danger" size="sm">Overdue</x-synapse-badge>
<x-synapse-badge color="success" icon="ph ph-check">Verified</x-synapse-badge>
```

**Props:**
- `color` (string, default: 'gray') - `'gray'`, `'brand'`, `'success'`, `'warning'`, `'danger'`, `'info'`
- `size` (string, default: 'md') - `'sm'`, `'md'`
- `icon` (string, optional) - Icon class — Phosphor (recommended) e.g. `'ph ph-check'`, or FontAwesome e.g. `'fa-solid fa-check'` (see [Icons](../README.md#icons))

---

### Nav Badge

The count beside a backend menu item. It is already called from the nav views (`menu-item`, `submenu-item`); a module only names a badge class on its menu item — see [README → Menu badges](../README.md#menu-badges).

```blade
<x-synapse-nav-badge :item="$item" />
```

| Prop | Type | Description |
|------|------|-------------|
| `item` | array | The menu item from `config('synapps.nav.backend')`. Renders nothing when it (and its visible children) has nothing to count. |

### Panel

Card-shell wrapper for list/form pages — a header (title + optional toolbar) and a body slot. Replaces the hand-rolled `rounded-2xl border ... dark:bg-white/3` card markup:

```blade
<x-synapse-panel :title="__('pm::client.list_title')">
    <x-slot:toolbar>
        <a class="btn primary" href="{{ backend_route('pm.client.create') }}">Add</a>
    </x-slot:toolbar>

    <table>...</table>
</x-synapse-panel>

{{-- Collapsible --}}
<x-synapse-panel title="Filters" collapsible collapsed>
    <table>...</table>
</x-synapse-panel>
```

**Props:**
- `title` (string, optional) - Panel header title
- `collapsible` (bool, default: false) - Make the body collapsible via the header
- `collapsed` (bool, default: false) - Start collapsed (only applies when `collapsible`)

**Slots:**
- Default - Panel body (table, form, etc.), unstyled
- `toolbar` (optional) - Header-right actions (add button, search, filter toggle), always visible even when collapsed

---

### Stat Tile

Single-metric card. Two variants:

```blade
{{-- variant="tile" (default): icon square + label/value --}}
<x-synapse-stat-tile icon="ph ph-invoice" color="blue" label="Total Invoices" value="2.613" />

{{-- :bordered="false" — flat gray-50 "quick stats" look, no icon --}}
<x-synapse-stat-tile label="Progress" value="42%" :bordered="false">
    <div class="mt-2 h-2.5 w-full rounded-full bg-gray-200 dark:bg-gray-700">
        <div class="h-2.5 rounded-full bg-blue-600" style="width: 42%"></div>
    </div>
</x-synapse-stat-tile>

{{-- variant="card": title-row layout, optional whole-card color tint --}}
<x-synapse-stat-tile
    variant="card" icon="ph ph-hourglass-medium" color="red" tinted
    label="Stuck Transactions" value="4,277" sublabel="> 30 min old"
/>

{{-- clickable card with a trend sublabel --}}
<x-synapse-stat-tile
    variant="card" clickable href="{{ route('...') }}"
    label="Transactions Today" value="0" sublabel="+0% vs yesterday (0)" sublabelColor="green"
/>
```

**Props:**
- `icon` (string, optional) - Icon class — Phosphor (recommended) e.g. `'ph ph-invoice'`, or FontAwesome e.g. `'fa-solid fa-file-invoice'` (see [Icons](../README.md#icons))
- `color` (string, default: 'gray') - `'gray'`, `'blue'`, `'indigo'`, `'purple'`, `'red'`, `'orange'`, `'green'`
- `label` / `value` (string, optional) - Caption / already-formatted value (caller formats numbers/currency); each can be overridden with a matching `<x-slot:label>` / `<x-slot:value>` for rich content
- `valueColor` (string, optional) - `variant="tile"` only - value text color override, same palette as `color`
- `bordered` (bool, default: true) - `variant="tile"` only - white card + border + rounded-xl vs gray-50 flat + rounded-lg (`false`)
- `variant` (string, default: 'tile') - `'tile'` or `'card'`
- `iconPosition` (string, default: 'left') - `variant="card"` only - `'left'` (icon square beside stacked title/value/sublabel), `'title'` (bare/circle icon inline in the title row), or `'background'` (large, low-opacity, cropped icon in the bottom-right corner)
- `iconBackground` (bool, default: true) - both variants - colored square/circle behind the icon (`true`) vs a bare colored icon glyph (`false`)
- `tinted` (bool, default: false) - `variant="card"` only - wash the whole card in `color` (bg/border/title/value/icon) vs a plain white card with a muted icon
- `sublabel` / `sublabelColor` (string, optional) - `variant="card"` only - small caption line under the value; `sublabel` can be overridden with `<x-slot:sublabel>`
- `href` (string, optional) - `variant="card"` only - wraps the tile in an `<a>`, implies clickable
- `clickable` (bool, default: false) - `variant="card"` only - hover-shadow/cursor-pointer affordance without requiring `href` (e.g. a `wire:click` card)

**Slots:**
- Default - Extra content below the value (a badge, a progress bar)
- `label` / `value` / `sublabel` - Override the matching prop with rich content

---

### Confirm Dialog

Modal confirmation for destructive actions:

```blade
<x-synapse-confirm-dialog
    title="Delete Item"
    message="This action cannot be undone. Are you sure?"
    confirmText="Delete"
    cancelText="Cancel"
    confirmColor="danger"
    icon="ph ph-trash" />
```

**Props:**
- `title` (string, default: 'Confirm Action')
- `message` (string, default: 'Are you sure you want to proceed?')
- `confirmText` (string, default: 'Confirm')
- `cancelText` (string, default: 'Cancel')
- `confirmColor` (string, default: 'danger')
- `icon` (string, default: 'ph ph-warning-circle')

---

### Drawer

Slide-out side panel:

```blade
<x-synapse-drawer title="Settings">
    <!-- Drawer content here -->
    <form>
        <!-- Form fields -->
    </form>
</x-synapse-drawer>
```

**Props:**
- `title` (string, required) - Drawer header title

---

### Lightbox

Full-screen image preview on a dark backdrop. Place it once, then open it from anywhere with a window event:

```blade
<x-synapse-lightbox name="preview" />

<button type="button" @click="$dispatch('open-lightbox-preview', { url: '/storage/photo.jpg', alt: 'Photo' })">
    Preview
</button>
```

**Props:**
- `name` (string, default: 'lightbox') - Scopes the open/close events

**Events (window):**
- `open-lightbox-{name}` - Opens with detail `{ url, alt? }`
- `close-lightbox-{name}` - Closes

Also closes on backdrop click, the close button, or Escape.

---

### Modal

```blade
<x-synapse-modal name="edit-user" title="Edit user" max-width="xl">
    <div class="syn-modal-body">…</div>
    <x-slot:footer>
        <button class="btn" type="button" @click="$dispatch('close-modal-edit-user')">Cancel</button>
        <button class="btn primary" type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save">Save</button>
    </x-slot:footer>
</x-synapse-modal>
```

| Prop / slot | Default | Description |
|---|---|---|
| `name` | `modal` | Event suffix — open with `$dispatch('open-modal-{name}')`, close with `close-modal-{name}` (also closes on Escape) |
| `max-width` | `xl` | Panel width |
| `title` | — | Renders the `syn-modal-header` with the title and a close button (screen-reader label "Close"), and labels the dialog |
| `labelledby` | — | Id of a heading the *content* renders. Names the dialog (`aria-labelledby`) without drawing a header — for a modal whose title lives in a child Livewire component |
| `footer` slot | — | Rendered in the `syn-modal-footer` action bar |

The panel is always `role="dialog" aria-modal="true"`, so a title-less modal is still announced as a dialog and still traps focus. Only its *name* depends on a heading: pass `title`, or `labelledby` pointing at a heading the content renders.

Without `title`/`footer` the slot is rendered as is, so hand-written `syn-modal-header` / `syn-modal-footer` markup keeps working — give that heading an id and pass it as `labelledby` so the dialog is announced with a name.

Note that pass-through `{{ $attributes }}` land on the outermost element (the one wrapping backdrop and container), not on the panel — `role`/`aria-*` cannot be forwarded to the panel that way, which is why `labelledby` exists.

The `footer` slot is rendered by the component that writes the `<x-synapse-modal>` tag. When the modal holds a *child* Livewire form (`<x-synapse-modal><livewire:thing-form /></x-synapse-modal>`), a `wire:click` in that slot would call the **outer** component — keep such a form's own footer inside the child view and use `labelledby`.

Event names are matched lowercase by the browser, so keep `name` lowercase.

### Toggler

```blade
<x-synapse-toggler id="is-active" name="is_active" label="Active" wire:model.live="isActive" />
```

Props: `id`, `name`, `label`, `checked` (false), `active-color` (`green`), `inline` (false).

### Toastr Notifications

Toast notifications for user feedback:

```blade
<x-synapse-toastr />
```

Place this component in your layout file to enable toast notifications throughout your application.

---

### Alert

Flexible alert component with dismissible functionality:

```blade
<!-- Basic alert -->
<x-synapse-alert type="success" message="Operation completed successfully!" />

<!-- Alert with title -->
<x-synapse-alert
    type="warning"
    title="Warning"
    message="Please review your changes before submitting." />

<!-- Non-dismissible alert -->
<x-synapse-alert
    type="danger"
    :dismissible="false"
    message="This is a critical error." />

<!-- Border accent style -->
<x-synapse-alert
    type="info"
    :border="true"
    message="Information with border accent" />

<!-- Solid background style -->
<x-synapse-alert
    type="success"
    :solid="true"
    message="Solid background variant" />

<!-- Custom HTML content using slot -->
<x-synapse-alert type="info" title="Getting Started">
    <p>Welcome! Check out <a href="/docs" class="underline">our documentation</a>.</p>
    <ul class="list-disc list-inside mt-2">
        <li>Feature 1</li>
        <li>Feature 2</li>
    </ul>
</x-synapse-alert>

<!-- Custom icon -->
<x-synapse-alert
    type="success"
    icon="ph ph-checks"
    message="Verified successfully!" />
```

**Props:**
- `type` (string, default: 'info') - Alert type: `'success'`, `'warning'`, `'danger'`, `'error'`, `'info'`
- `title` (string, optional) - Alert title
- `message` (string, optional) - Alert message (can also use slot for HTML)
- `dismissible` (boolean, default: true) - Whether alert can be closed
- `icon` (string, auto-assigned) - Icon class, Phosphor by default (auto-assigned based on type) — pass a FontAwesome class instead to override (see [Icons](../README.md#icons))
- `border` (boolean, default: false) - Show left border accent
- `solid` (boolean, default: false) - Use solid background instead of light variant

**Features:**
- 4 alert types with auto-assigned icons (success, warning, danger, info)
- Dismissible with Alpine.js smooth animations
- Dark mode support
- Supports both props and slot content
- Border and solid style variants
- Accessible with ARIA attributes

**Default Icons:**
- Success: `ph ph-check-circle`
- Warning: `ph ph-warning`
- Danger/Error: `ph ph-warning-circle`
- Info: `ph ph-info`

**Common Use Cases:**

```blade
<!-- Flash messages -->
@if(session('success'))
    <x-synapse-alert type="success" :message="session('success')" />
@endif

@if(session('error'))
    <x-synapse-alert type="danger" :message="session('error')" />
@endif

<!-- Validation errors -->
@if($errors->any())
    <x-synapse-alert type="danger" title="Validation Errors">
        <ul class="list-disc list-inside space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-synapse-alert>
@endif

<!-- Multiple alerts -->
<div class="space-y-4">
    <x-synapse-alert type="success" message="Payment received." />
    <x-synapse-alert type="warning" message="Subscription expires in 3 days." />
    <x-synapse-alert type="info" message="New features available." />
</div>
```

---

### Copy Button

Copies `text` to the clipboard with "Copied" / "Copy failed" feedback (icon swap + `aria-live`). Falls back to a hidden textarea + `execCommand('copy')` where the Clipboard API is unavailable (non-HTTPS hosts).

```blade
<x-synapse-copy-button text="packages/synmod-example/src" label="Copy path" />
<x-synapse-copy-button :text="$token" label="Copy token" :icon-only="false" />
```

| Prop | Type | Default | Notes |
|---|---|---|---|
| `text` | string | — | Required |
| `label` | string | `Copy` | Accessible name and visible label |
| `iconOnly` | bool | `true` | Hide the visible label |

## Planning & Layout Components

### Steps

Server-driven step indicator; the host Livewire component owns `$step`.

```blade
<x-synapse-steps :steps="['Details', 'Media', 'Review']" :current="$step" go-method="goToStep" />
```

| Prop | Type | Default | Notes |
|---|---|---|---|
| `steps` | array | `[]` | Labels in order |
| `current` | int | `1` | 1-based, clamped to the valid range |
| `goMethod` | string | `null` | Called as `goToStep(n)` — completed steps only |

### Split Pane

```blade
<x-synapse-split-pane aside-width="20rem" resizable storage-key="example-nodes" toggle-label="Show tree">
    <x-slot:aside>...tree...</x-slot:aside>
    ...editor...
</x-synapse-split-pane>
```

| Prop | Type | Default | Notes |
|---|---|---|---|
| `asideWidth` | string | `20rem` | number + `px`/`rem`/`%` |
| `resizable` | bool | `false` | Drag handle between panes |
| `storageKey` | string | `null` | Remembers the dragged width in localStorage (resizable only) |
| `toggleLabel` | string | `Toggle panel` | Mobile show/hide button |

Below `lg` the panes stack and the aside hides behind the toggle.

### Kanban

Layout for Livewire 4's native `wire:sort` groups — no custom JS.

```blade
<x-synapse-kanban sort-method="moveItem">
    @foreach ($this->columns as $column)
        <x-synapse-kanban.column :id="$column['id']" :label="$column['label']" :color="$column['color']" :count="count($column['items'])" empty-text="Drop items here">
            @foreach ($column['items'] as $item)
                <x-synapse-kanban.card :id="$item->id" wire:key="card-{{ $item->id }}">{{ $item->name }}</x-synapse-kanban.card>
            @endforeach
        </x-synapse-kanban.column>
    @endforeach
</x-synapse-kanban>
```

```php
public function moveItem(string $id, int $position, string $columnId): void
{
    // $columnId is always the destination column; persist status + position.
}
```

| Component | Props |
|---|---|
| `x-synapse-kanban` | `sortMethod`, `group` (default `kanban`) |
| `x-synapse-kanban.column` | `id`, `label`, `color` (`gray`/`brand`/`success`/`warning`/`danger`/`info`), `count`, `emptyText` |
| `x-synapse-kanban.card` | `id` — drag by the handle; wrap inner buttons in `wire:sort:ignore` if needed |

### Calendar

Month grid (md+) and agenda list (mobile), built server-side.

```blade
<x-synapse-calendar :month="$month" :events="$this->events" nav-method="setMonth" event-method="openEvent" day-method="createOn" week-start="monday" />
```

| Prop | Type | Default | Notes |
|---|---|---|---|
| `month` | string | current month | `Y-m`; malformed → current month |
| `events` | array | `[]` | `[['id', 'title', 'date' => 'Y-m-d', 'color']]`; malformed entries are skipped |
| `navMethod` | string | `null` | `setMonth('Y-m')` from prev / today / next |
| `eventMethod` | string | `null` | `openEvent('id')` |
| `dayMethod` | string | `null` | `createOn('Y-m-d')` from a day number or "+N more" |
| `weekStart` | string | `monday` | or `sunday` |

Each day shows up to 3 events plus "+N more".

## List Helpers

### Empty State

```blade
@forelse ($members as $member)
    …
@empty
    <tr><td colspan="5">
        <x-synapse-empty-state icon="ph ph-users" title="No members yet" text="Add the first member.">
            <a class="btn primary" href="{{ route('members.create') }}" wire:navigate>New member</a>
        </x-synapse-empty-state>
    </td></tr>
@endforelse
```

| Prop | Default | Description |
|---|---|---|
| `title` | — | Heading |
| `text` | — | Optional explanation |
| `icon` | `ph ph-tray` | Icon class |

The slot is the optional action. On filtered lists, show a "no results" title with a clear-filters button instead of the create action.

### Per-Page Selector

```blade
<x-synapse-per-page-selector :paginator="$items" />
```

Renders a 10/15/25/50 page-size `<select>` bound to the host's `$perPage` (`wire:model.live="perPage"`) next to `$paginator->links()`. The host component must have a public `$perPage` property.

| Prop | Default | Description |
|---|---|---|
| `paginator` | — | The paginator whose `links()` are rendered beside the selector |
| `label` | `Per page` | Accessible name (`aria-label`) of the `<select>`; it has no visible label |

### Sort Icon

```blade
<th><x-synapse-sort-icon field="name" :sort-field="$sortField" :sort-direction="$sortDirection" /> Name</th>
```

A link that calls the host's `sortBy('{field}')` and shows `ph-arrows-down-up` / `ph-caret-up` / `ph-caret-down`.

### Excel Export Button

```blade
{{-- Sync: the action returns a download response --}}
<x-synapse-excel-export-button mode="sync" action="exportToExcel" label="Export" exporting-label="Exporting…" />

{{-- Async: background export tracked by an ExcelExport task (see WithExcelExport) --}}
<x-synapse-excel-export-button
    mode="async"
    :task="$this->exportTask"
    action="exportToExcel"
    download-action="downloadExport"
    dismiss-action="dismissExport"
    label="Export" processing-label="Processing…" download-label="Download"
    failed-label="Export failed" dismiss-label="Dismiss"
/>
```

In async mode the button shows three states — idle, processing (polls), completed (download + dismiss) or failed. See [EXCEL.md](EXCEL.md#livewire-export-button).

## Advanced Select

A fully featured, reusable **Livewire + Alpine.js select component**. The replacement for the removed `select`/`multi-select` components (see the note above).

> **v3.0.0:** the search input now lives inside the dropdown instead of nested in the bordered main box (fixes a double-border look under themes that border both), gained a rotating dropdown-arrow indicator, and the clear/pill-remove buttons now use Phosphor icons instead of a bare `✕` glyph.

Supports:

- ✅ Single or multiple selection
- ✅ Searchable dropdown (static or remote)
- ✅ Static or dynamic data
- ✅ Auto-loads selected values from wireModel (with AJAX support)
- ✅ Preloaded default values
- ✅ Create new options on-the-fly
- ✅ Optional "live" syncing with Livewire
- ✅ Works with Livewire SPA navigation

### Props

| Prop | Type | Default | Description |
|------|------|----------|-------------|
| `wireModel` | `string` | — | The Livewire property to bind to. |
| `searchMethod` | `string\|null` | `null` | Name of a Livewire method for remote searching. |
| `loadMethod` | `string\|null` | `null` | Name of a Livewire method to load initial values by ID(s). |
| `multiple` | `bool` | `false` | Enables multiple selection mode. |
| `placeholder` | `string` | `'Select an option'` | Placeholder text shown in the input. |
| `labelField` | `string` | `'label'` | The field name used for option labels. |
| `valueField` | `string` | `'value'` | The field name used for option values. |
| `preload` | `array` | `[]` | Preselected options (for edit forms). |
| `options` | `array` | `[]` | Static options for client-side use. |
| `minSearchLength` | `int` | `2` | Minimum characters before triggering remote search. |
| `live` | `bool` | `false` | If `true`, updates Livewire instantly as user selects. |
| `allowCreate` | `bool` | `false` | If `true`, allows creating new options by pressing Enter. |
| `autoLoad` | `bool` | `true` | If `true`, auto-loads selected values from wireModel on init. |

### Keyboard and labelling

- The select box is a focusable `role="combobox"`: Enter, Space or ArrowDown opens it; in the dropdown, ArrowUp/ArrowDown move through the options, Enter picks one, and Escape closes it and returns focus to the box.
- `id`, `aria-label`, `aria-labelledby` and `aria-describedby` are forwarded to the box. A `<label for>` cannot name a `div`, so name the select with `aria-labelledby` (pointing at the visible label's `id`) or `aria-label`.
- Inside an `<x-synapse-field>` none of that is needed: the select reads the field's label id, error/hint description and invalid/required state through `@aware` and applies them itself. Anything you pass explicitly wins, and an explicit `aria-label` suppresses the field's label id.
- In single-select mode the clear button sits beside the dropdown arrow, at the right edge of the box.

### Usage Examples

#### Static Single Select

```blade
<x-adv-select
    wire-model="role"
    :options="[
        ['value' => 'admin', 'label' => 'Administrator'],
        ['value' => 'editor', 'label' => 'Editor'],
        ['value' => 'user', 'label' => 'User'],
    ]"
/>
```

#### Static Multiple Select

```blade
<x-adv-select
    wire-model="permissions"
    :multiple="true"
    :options="[
        ['value' => 'create', 'label' => 'Create'],
        ['value' => 'read', 'label' => 'Read'],
        ['value' => 'update', 'label' => 'Update'],
        ['value' => 'delete', 'label' => 'Delete'],
    ]"
/>
```

#### Remote Search

```blade
<x-adv-select
    wire-model="user_id"
    search-method="searchUsers"
    placeholder="Search users..."
/>
```

In your Livewire component:

```php
public function searchUsers($query)
{
    return User::where('name', 'like', "%{$query}%")
        ->take(10)
        ->get()
        ->map(fn($user) => [
            'value' => $user->id,
            'label' => $user->name,
        ]);
}
```

#### Auto-Load with AJAX (Edit Forms)

**New in v1.5:** The component now automatically loads selected values from the wireModel!

```blade
{{-- Static options: auto-finds labels from options --}}
<x-adv-select
    wire-model="category_id"
    :options="$categories"
/>

{{-- AJAX mode: uses loadMethod to fetch label by ID --}}
<x-adv-select
    wire-model="user_id"
    search-method="searchUsers"
    load-method="loadUser"
    placeholder="Select user..."
/>

{{-- Multiple with auto-load --}}
<x-adv-select
    wire-model="tags"
    :multiple="true"
    :allow-create="true"
/>
```

In your Livewire component:

```php
public $user_id = 5; // Component auto-loads user #5 on init

// Load method for fetching initial values by ID
public function loadUser(int|array $ids): array
{
    $query = User::query();

    if (is_array($ids)) {
        $query->whereIn('id', $ids);
    } else {
        $query->where('id', $ids);
    }

    return $query->get(['id as value', 'name as label'])->toArray();
}
```

**How it works:**
1. Component reads the current value from `wireModel`
2. Tries to find labels in static `options` first
3. If `loadMethod` is provided, calls it to fetch full objects
4. Falls back to using value as label if neither works

#### Manual Preload (Legacy)

You can still manually pass preloaded values:

```blade
<x-adv-select
    wire-model="category_id"
    :options="$categories"
    :preload="[
        ['value' => $product->category_id, 'label' => $product->category->name]
    ]"
/>
```
