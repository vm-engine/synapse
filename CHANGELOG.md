# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [3.10.2] - 2026-11-13

### Fixed
- A full `synapps:patch` no longer duplicates `base.*` content into a split `resources/css/app.css` / `resources/js/app.js`. The `@source` patcher and the global-helpers patcher (`withBack`, the `$withBack` magic, the back-URL `mousedown` listener) still targeted `app.*` and only checked `app.*` for what was there, so every run on a split project prepended the 10 `@source` lines to `app.css` and appended the helpers to `app.js` — registering `withBack` twice and binding the listener twice on backend pages. Both now write to the entry every layout loads: `base.*` once it exists, `app.*` only before the split (the migration then carries it into `base.*`); a split `app.*` with no `base.*` is left alone. Reported from 4vmcloud's 3.10.1 verification.

## [3.10.1] - 2026-11-13

### Fixed
- A full `synapps:patch` (no object, and so also `synapps:patch --force`) no longer drops a project's own imports from `resources/js/app.js` / `resources/css/app.css`. The pre-split import patchers re-added the direct `synapps.js` / `synapps.css` import to an already-split file; the focused-import rewrite later in the same run then took the file for a monolith and overwrote it with its single import — losing `synapse-rte.js`, a module's `cms.js` and anything else the host had added, with no fresh backup (the `.bak` is write-once, so it held an older copy if one existed). The import patchers now leave a split entry alone (its `base.*` exists, or it imports `synapps-app.*`), and the rewrite keeps every line `base.*` does not already carry, so a genuine monolith migration still moves those lines rather than dropping them. Reported from 4vmcloud's 3.8.1 → 3.10.0 upgrade.
- The `--force` note said the host entry points were "left untouched" even when the same run had rewritten `app.js`. It now says `--force` never overwrites them and that the regular patches above may still have edited them.

## [3.10.0] - 2026-11-13

### Added
- Indonesian validation, auth, password and pagination messages ship with the package: `lang/id/{validation,auth,passwords,pagination}.php`, with a `validation.attributes` map for field names shared across modules. Laravel ships these in English only and Synapse apps run with `APP_FALLBACK_LOCALE=id`, so every project had to keep its own `lang/id/validation.php` — and `auth.failed`, `passwords.sent` and the pagination labels rendered as raw keys in projects that had not written theirs. `SynapseLangServiceProvider` (which already owns the translator, but had only commented-out code) now looks group files up in the framework's English, then Synapse's `lang/`, then the project's `lang/`, each overriding the previous key by key — so a project overrides one message by writing only that key. Tests check the lookup order, that every shipped file has every framework key with the same `:placeholders`, and that a project override wins while the rest still comes from Synapse. **Upgrading:** a project's existing `lang/id/validation.php` keeps working (it still wins); delete it, or cut it down to its own overrides.
- Eight components replacing partials that projects and modules kept copying:
  - `<x-synapse-breadcrumbs :breadcrumbs="$this->breadcrumbs" />` — the backend page title + trail. Same markup as the `layouts.partials.breadcrumbs` include it replaces, plus `aria-label` on the `<nav>`, `aria-current="page"` on the last crumb and hidden decorative chevrons. Falls back to `/` for the home link when synapps-auth is not installed.
  - `<x-synapse-loader />` — the global loading overlay, on while `wire:navigate` runs or a Livewire request is in flight. Its Alpine half (`synapseLoader`) now lives in `synapse-components.js`; the old partial registered `globalLoader` from an inline `<script>`, which does not run under a strict CSP or after a morph. The stub app layouts render it.
  - `<x-synapse-confirm-button>` — opens the confirm dialog for a Livewire method; `token` (a `WithDeleteToken` delete token) for deletes, `param` for anything else, icon+tooltip or `labelled`, optional `confirm-text`.
  - `<x-synapse-save-button>` — primary button with a spinner and "Saving…" while its action runs; `target` adds a file-upload property to the busy state, `submit` makes it a form submit button.
  - `<x-synapse-form-action-bar>` — the sticky required-hint / Cancel / Save bar for long page forms (`.syn-form-action-bar`); slot for extra buttons.
  - `<x-synapse-section-heading>` — left-hand title + help of a two-column form section (`.syn-section-label`).
  - `<x-synapse-modal-header>` / `<x-synapse-modal-footer>` — header and Cancel/Save footer for a child Livewire form inside `<x-synapse-modal labelledby="modal-{name}-title">`, where the title depends on the form's state so the modal's own `title` cannot be used.

  Method and modal names interpolated into `wire:click` / `$dispatch` go through `SanitizesWireArguments`. The Indonesian strings are in `lang/id.json`.

### Changed
- The package's test suite always runs in English: `phpunit.xml` forces `APP_LOCALE`/`APP_FALLBACK_LOCALE=en` and `TestCase` pins `app.locale`/`app.fallback_locale` (a `force="true"` env cannot override a value exported in `$_SERVER`), with a test that fails otherwise. Translation tests use a throwaway locale instead of asserting `lang/id.json` text.
- Synapse's own views, stubs and stub modules (home, settings, todos, including their `module.json` menu icons) use Phosphor instead of Font Awesome. A spinner is `ph ph-spinner syn-spin`.

### Deprecated
- `synapps::components.layouts.partials.breadcrumbs` and `…partials.loader` are one-line shims over the new components, kept so existing `@include` calls work. Use the components.
- Font Awesome. The `@import` in `synapps.css` stays so a project's own `fa-*` classes keep rendering; it will be removed in 4.0.

### Fixed
- `<x-synapse-radio-buttons>`: the selected value reaches Alpine as a JSON string. It was interpolated bare into `x-data="{ selected: … }"`, so an empty value rendered `{ selected:  }` (a parse error that broke every radio on the page) and any non-numeric value became an undefined variable — or extra JavaScript — in the expression.
- `<x-synapse-loader>` covers the whole `wire:navigate`: it now starts on `livewire:navigate` (the click) instead of `livewire:navigating`, which Livewire fires only after the page has been fetched, right before the swap — so the overlay never showed during the wait. A navigation another listener cancels resets it. (The old `partials/loader` had the same flaw.)
- `<x-synapse-adv-select>`: the default placeholder is translated (`Select an option` → "Pilih opsi"). It was a bare English default argument, which never passes through `__()`, so the translation coverage test could not see it.
- `<x-synapse-password>`, `<x-synapse-toastr>` and `<x-synapse-alert>` translate their placeholder and close-button `aria-label`s ("Enter your password", "Dismiss notification", "Close alert"). A new test fails on any literal text in a component's `placeholder`, `aria-label` or `title`, which the `__()`-based translation test could not see.
- `<x-synapse-password-generator>`: the three icon-only buttons have a translated accessible name (`aria-label` + `title`: "Generate password", "Apply password", "Copy password") — they carried an English `title` only — and the input placeholder is translated too.
- `.syn-modal-header` and `.syn-modal-footer` are `sticky` (with the panel's background, so content scrolls underneath). `.syn-modal-panel` is the scroll container, so in a long modal the title, close button and action buttons scrolled out of view.
- `<x-synapse-adv-select>` inside a modal or drawer: the open dropdown is pinned to the viewport (`position: fixed`, re-placed on scroll/resize, opening upward when there is more room above). It was `absolute` inside an `overflow-y-auto` panel, so the list either stretched the panel — the modal grew a scrollbar and scrolled — or was clipped at its edge. Adv-selects outside a modal or drawer are unchanged. **The Alpine half has no automated test** (no JS harness); modals and drawers were browser-verified downstream. Projects pick this up with `synapps:patch --force` and `npm run build`.

## [3.9.0] - 2026-11-05

### Added
- `<x-synapse-datepicker mode="month">` renders a year/month grid instead of a day calendar, via Flatpickr's bundled `monthSelect` plugin, and formats as `Y-m` — the same value `<input type="month">` submits, so an existing `wire:model` and `date_format:Y-m` validation need no change. A `dateFormat` passed in `flatpickr` still wins and is applied to both the grid and the input text. This was the last control with no Synapse equivalent, forcing projects to keep native `<input type="month">` elements. The plugin's own stylesheet is imported alongside it and the month cells are themed to match the day grid. **The Alpine half of this has no automated test** — the package has no JS harness, so only the Blade wiring and the stub's plugin registration are covered; the behaviour itself was verified by inspection.
- `synapps:patch --force` reinstalls the two package-owned stub files, `synapps/resources/js/synapse-components.js` and `synapps/resources/css/synapse-components.css`, keeping the current file as `.bak`. `synapps:patch` itself only ever *created* them and then skipped them forever, so a fix to the package's Alpine or component CSS never arrived through the command that installed it. `synapps:copy-resources --js --css --force` did already replace them — but it copies whole stub directories, so asking for the components file also rewrote `synapps.js`, `synapse-rte.js`, `sb-admin-2.min.js` and `synapps.css`, and it writes no backup of what it overwrote. `patch --force` is the narrow, reversible path: those two files, nothing else, previous version kept as `.bak`. It applies whatever object is named — `synapps:patch js --force` forces the component files too — so callers need not know the files happen to sit in the `layouts` subset, while the named object still decides every other patch. Host-owned entry points (`resources/{css,js}/{base,app,frontend,guest}.*`, `vite.config.js`) are excluded and listed on every forced run. Run `npm run build` afterwards.

### Fixed
- `<x-synapse-stat-tile>`: a zero renders. `label`, `value` and `sublabel` were guarded with `@if ($value)`, and `0` and `"0"` are both falsy in PHP, so a count of zero produced a tile with a label and nothing under it — which reads as broken rather than as zero, and is common on an empty tenant. The guards now use `filled()`, so only `null` and `''` count as absent; a `<x-slot:value>` still works, since `ComponentSlot` implements `Stringable` and `filled()` measures its contents. No caller-side workaround existed: casting could not help, because `"0"` is falsy too.
- `<x-synapse-stat-tile>`: the `tile` variant passes attributes through. Its root was a bare `<div class="…">` while the `card` variant rendered `{{ $attributes->class([…]) }}`, so a caller's `class`, `wire:key`, `aria-*` and `data-*` were dropped with no error — silently costing a `wire:key` inside a `@foreach` and an active-filter highlight.
- `<x-synapse-adv-select>`: the dropdown's filter input has an accessible name (`aria-label`, default `__('Search')`, overridable with the new `searchLabel` prop). It carried only a `placeholder`, which is not an accessible name, so every instance added an unlabelled control to the page — invisible to a call-site audit, since the defect was in the component.

### Documentation
- `docs/COMMANDS.md` documents what `synapps:copy-resources --force` actually overwrites. It is directory-granular and keeps no backup, so `--js` rewrites all four JS stubs (`synapps.js`, `synapse-components.js`, `synapse-rte.js`, `sb-admin-2.min.js`) and `--css` rewrites `synapps.css` as well as `synapse-components.css` — including files a project may have edited in place, and including ones it did not previously have. Without `--force` the command does not refuse but prompts per directory. A project upgrading 3.7 → 3.8 needed this copy regardless (3.8.4's `modal.blade.php` calls a `trapTab()` that a frozen `synapse-components.js` does not define, so every modal would throw on Tab) and was surprised by the collateral files. Both command sections now cross-reference each other so a reader lands on the right one.
- `docs/COMPONENTS.md` records the two `:flatpickr` configurations now verified in the field — datetime (`enableTime` + `dateFormat` + `time_24hr`) and time-only (`noCalendar`) — since that prop had no call sites anywhere until now and was therefore untested in practice.

## [3.8.5] - 2026-11-05

### Fixed
- `lang/id.json` gains the three keys the package emitted but never translated: `Per page` (the per-page selector's `aria-label`, added in 3.8.4), and `Copied` / `Copy failed` (`<x-synapse-copy-button>`'s feedback, missing since the component landed). The app locale on Synapse projects is `id` with no English fallback, so an untranslated key renders as the key itself — the per-page selector's accessible name read "Per page" to an Indonesian screen-reader user, which made the 3.8.4 fix useless to them.
- A test now asserts that every bare `__('…')` key the package's own views and classes emit has an entry in `lang/id.json`, so a new untranslated string fails the suite instead of shipping. It walks all 243 files under `resources/views/` and `src/` recursively; namespaced keys (`module::group.key`) are the consuming module's to translate and are skipped.

### Documentation
- `docs/COMPONENTS.md` opens with a note that the package already translates its components' own text in `lang/id.json`, and that a host app's `lang/{locale}.json` silently overrides it. Nothing documented this before, so projects had no way to know the keys existed; one had reimplemented 20 of them and was shadowing the package's wording in two places with no error to show it.

## [3.8.4] - 2026-11-05

### Fixed
- `<x-synapse-field>`: a slotted control now gets the field's aria wiring. The slot branch forwarded none of it, so every field wrapping a select, datepicker or adv-select — the majority of real usage — had only `<label for>` and no error association. The component exposes the wiring as `$fieldAria` (label id, `aria-describedby` for the error/hint ids, `aria-invalid`, `aria-required`), and `<x-synapse-adv-select>` / `<x-synapse-datepicker>` read it through `@aware` and apply it themselves, so the call site writes no aria and an explicit attribute still wins. Raw HTML in a slot is out of Blade's reach and still writes its own; the component docblock and `docs/COMPONENTS.md` spell out that contract.
- `<x-synapse-modal>`: the panel is always `role="dialog" aria-modal="true"`. Both were conditional on `title`, so a title-less modal — one whose header is owned by a nested Livewire form, a legitimate and common shape — was an unlabelled `<div>` with no dialog role. `aria-labelledby` is still emitted only when `title` or `labelledby` names a heading to point at.
- `<x-synapse-per-page-selector>`: the page-size `<select>` has an accessible name (`aria-label`, defaulting to the translatable "Per page", overridable with the new `label` prop). It has no visible label, so it was an unlabelled control in every list using the component.

## [3.8.3] - 2026-11-04

### Fixed
- `synapps/config/compiled/checksum` is no longer documented in `CLAUDE.md` or `docs/CONFIGURATION.md`. The config generators stopped writing that file before 3.x, so a scaffolded module test asserting it exists (as the `home` stub's `ConfigTest` once did) failed against a correctly compiled project.
- `SynAppsDiscoverComposerTest` restores the application's original base path in `afterEach` and creates its sandbox through the `File` facade, matching the sandbox convention the other command tests use. The old version pointed the app at a throwaway directory and never pointed it back, so under `pest --parallel` a later test in the same process could inherit the deleted path.

## [3.8.2] - 2026-11-03

### Fixed
- `synapse:patch-composer` appends JSON list entries instead of merging them by index. Merging `repositories` (or `autoload.classmap`, or any other list) by numeric key overwrote whichever entry happened to sit at the same position instead of adding the new one, so patching `composer.local.json` silently destroyed existing repository entries. Re-patching no longer duplicates entries either.
- `synapps:discover` reports dev-dependency counts from the dev counters. The "NPM Dev Dependencies" and "Composer Dev Dependencies" lines read the non-dev counters, so dev counts were never shown and the non-dev counts were printed twice.
- `synapps:discover` no longer runs a bare `composer update`. It now updates only the packages whose constraint it just added or raised (`composer update <packages> --with-all-dependencies`), so an unrelated lock inconsistency can no longer fail the run after Composer has started deleting packages from `vendor/`.
- `synapps:discover` runs `composer dump-autoload` — not `composer update` — when the only change is a removed module's PSR-4 namespace.
- `synapps:discover` compares Composer constraints on their parsed lower bound instead of comparing the constraint strings, so compound constraints (`>=1.0`, `^1.0|^2.0`) order correctly. A module's branch constraint (`dev-main`, `@dev`) no longer overwrites a stable constraint in the host's `composer.json`; it warns and leaves it alone.
- `synapps:discover` runs `composer`/`npm` through Laravel's `Process` facade instead of `exec()`, so their output is shown, a non-zero exit is reported with the failing command and a recovery hint, and the command exits non-zero.

## [3.8.1] - 2026-10-30

### Fixed
- `<x-synapse-currency>`: select-on-focus now matches any all-zero value (`0,00`, `0.000`, not just `0`), so clicking a zeroed amount configured with `decimals` and typing replaces it instead of appending to the zero. The check is written as `replaceAll()` chains rather than a regex, which Alpine's CSP-safe build cannot parse.

## [3.8.0] - 2026-10-29

### Changed
- `<x-synapse-adv-select>`: the single-select clear button now sits beside the dropdown arrow instead of right after the label, and has an accessible name ("Clear selection"). The selected label truncates and is readable in dark mode. Re-copy `synapse-components.css` and rebuild.
- Tooltips (`.has-tooltip > .tooltip`) are hidden with opacity instead of `invisible`, so their text is the accessible name of icon-only buttons, and they now also show on keyboard focus. Re-copy `themes/default/css/theme-base.css` and rebuild.
- `.syn-empty-state` text raised to `gray-500` / dark `gray-400` for contrast.

### Added
- `<x-synapse-adv-select>` is a focusable `role="combobox"`: Enter/Space/ArrowDown open it, ArrowUp/ArrowDown + Enter choose an option, Escape closes it and refocuses the box. It forwards `id`, `aria-label`, `aria-labelledby` and `aria-describedby`. Re-copy `synapse-components.js` and rebuild.
- `Breadcrumbs::current()`: the last crumb's label.
- `<x-synapse-field>`: `inputClass` prop (classes for the control itself, e.g. `text-right`, where `class` styles the form-box), `type="textarea"` with `rows`, and the label now carries `id="{id}-label"` so a non-labelable slot control (adv-select) can be named with `aria-labelledby`.
- `<x-synapse-modal>` `labelledby` prop: labels the dialog from an id its content renders, for a modal holding a child Livewire form with its own header.

### Fixed
- Icon buttons (`.btn-icon`) are 44×44 squares, the WCAG AA hit floor — the 16px glyph was the whole tap area before. Row-action cells keep their buttons on one line, so table rows grow about 12px. The sizing rule is declared after `.has-tooltip`, whose `inline-block` would otherwise win at equal specificity. Re-copy `themes/default/css/theme-base.css` and `synapse-components.css`, then rebuild.
- `<x-synapse-modal>` moves focus into the dialog on open (first `[autofocus]`, else the first focusable control), keeps Tab inside it, and returns focus to the element that opened it. Escape already closed the dialog; focus simply never left the trigger. Re-copy `synapse-components.js` and rebuild.
- `.btn-icon` hover transition dropped from 500ms to the 150ms motion token.
- `synapps:copy-resources` deleted every destination directory before copying, so it removed host files the stubs do not ship — including the per-layout `synapps-{app,base,frontend,guest}.css/.js` entry points that `synapps:patch` creates and Vite builds from, silently breaking the next build. It now merges into the destination. `SynapseUtils::copyDirectory()` unlinks a symlinked or hard-linked destination file before writing, which is what the wipe had been guarding against.
- `composer.json` branch alias moved to `3.8.x-dev`, so a path/`@dev` checkout satisfies `^3.8` constraints.
- The settings module stubs (`configuration-list`, `languages-list`) no longer carry a `#[Layout]` attribute; Livewire resolves the layout for them.
- `<x-synapse-field>` emitted two `class` attributes (`@class([...])` plus `$attributes->only('class')`), so a caller's class — a grid span like `sm:col-span-2` — was silently dropped by the browser. The caller's class is now merged into the form-box. Its hint uses `.form-help` instead of repeating that class's utilities.

## [3.7.3] - 2026-10-24

### Fixed
- `<x-synapse-currency raw>` with `wire:model.live` sent a request on every keystroke (it entangled with `.live`, which bypasses wire:model's debounce), so the global loader flashed while typing. It now stays deferred and sends one `$commit` once typing pauses: 150ms, 250ms for a bare `.debounce`, or `.debounce.Nms`. Re-copy `synapse-components.js` and rebuild.

## [3.7.2] - 2026-10-24

### Added
- `<x-synapse-currency>` `decimals` prop (decimal places the mask allows, default 0) and `raw` prop: the input still shows `1.250.000,50` while `wire:model` receives the plain number `1250000.50`, so decimal money fields need no server-side parsing. Opt-in; without it the formatted text is sent as before. Needs the new `synapseCurrency` component in `synapse-components.js` (re-copy the stub and rebuild).
- `<x-synapse-field type="currency">` renders a raw currency input with the field's label, error and hint wiring (`prefix` = symbol, `decimals`).

### Changed
- `<x-synapse-currency>` sets `inputmode` (`numeric`, or `decimal` when `decimals` > 0) so phones show a number keypad, and omits the symbol box when `currency` is empty.

### Fixed
- The default and application theme `frontend.blade.php` stubs linked the dashboard via a hardcoded `backend.home.dashboard` route name, which breaks when the back office name prefix is configured; they now use `backend_route()` (guarded by `function_exists`, since synapps-auth is optional). Projects that copied the layout: replace `route('backend.home.dashboard')` with `backend_route('home.dashboard')` and the `Route::has()` check with `Route::has(backend_route('home.dashboard', nameOnly: true))`.
- Docs no longer suggest `route('backend.…')` for back office links.

## [3.7.1] - 2026-10-23

### Added
- `<x-synapse-field>` `error` prop (validation error key when it differs from the model path) and `prefix` prop (input-group text before the input, e.g. `Rp`).

## [3.7.0] - 2026-10-23

### Added
- `<x-synapse-modal>` `title` prop and `footer` slot: the title renders the `syn-modal-header` with a labelled close button and labels the dialog (`role="dialog"`, `aria-labelledby`); the footer slot renders the `syn-modal-footer` action bar. Modals without them render as before.
- `<x-synapse-field>`: label, wire-bound input and error/hint in a `form-box`, linked with `for`, `aria-invalid` and `aria-describedby`; the slot replaces the input for selects and other controls.
- `<x-synapse-empty-state>`: icon, title, text and an optional action slot for empty lists and panels.

### Changed
- `.syn-modal-close` gets padding so the close button is a 44px touch target (re-copy `synapse-components.css`).

### Fixed
- The `dev-master` branch alias still said `3.4.x-dev`, so a path checkout could not satisfy `^3.6` constraints (e.g. `synmod-crm`); it is now `3.7.x-dev`.
- The breadcrumbs partial's page title read `pageName` from the page's `x-data`, so every page that never declared it threw `Undefined variable: pageName` under the CSP Alpine build (e.g. the tenancy pages, the home dashboard). It now reads `$data.pageName || ''`.
- The home module stub's `module.json` uses `__home::menu.fe.index`, which neither `lang/en` nor `lang/id` defined; both now do.
- Indonesian (`lang/id.json`) had only the mail strings, so the calendar, colour picker, notification bell/list and search palette showed English on an `id` app; their 18 strings are now translated. The drawer, lightbox and confirm dialog's screen-reader "Close" is translatable too.

## [3.6.2] - 2026-10-21

### Fixed
- Theme JS registered Alpine components only in an anonymous `alpine:init` callback, so they were missing after a cross-layout `wire:navigate`: the default theme's `theme-base.js` (modal, tooltip, tabs, dropdown, toasts, …) and `theme-app.js` (sidebar, dark mode, notifications, …), and the application theme's shared `theme-base.js` components. They now register right away when Alpine is already running.
- The generic fallback layout `components/layouts/layout.blade.php` loaded only the `app` entries, so pages using it (e.g. the todos stub module) had none of the synapse components (`Undefined variable: synapseAdvSelect` / `synapseDatepicker` / `toastrRoot` …) — `synapse-components.js` lives in the `base` entry since the per-layout split. It now loads `base.css` + `base.js` too; `layouts/app.blade.php` likewise, and the Bootstrap `app-bootstrap.blade.php` loads `base.js`.
- `synapps:patch js` added the `$withBack` Alpine magic inside an anonymous `alpine:init` callback; it now registers right away when Alpine is already running. Existing projects: update the block in `resources/js/base.js` (rule G lists it).

### Added
- `synapps:upgrade-audit` / `tools/synapse-upgrade-audit.php` rule G (manual review): an Alpine registration inside an anonymous `alpine:init` callback — copies of layouts, error pages, theme JS or module views made before this release — which breaks after a cross-layout `wire:navigate`.

## [3.6.1] - 2026-10-21

### Fixed
- **Alpine components missing after a cross-layout `wire:navigate`** (guest → frontend/app after login, app → frontend, …): the first page logged `Undefined variable: frontendHeader` / `headerClass` / `ariaExpanded` and the header's scroll shadow / mobile menu stayed broken until a full reload. Livewire waits only for new `<head>` scripts before starting Alpine on a navigated page, but every layout rendered `@themeScripts('<layout>')` at the end of `<body>`. All theme layout stubs (`stubs/themes/{default,application}/layouts/*`) and the fallback `components/layouts/app.blade.php` now render it in `<head>` right after `@themeStyles`; the Bootstrap fallback layouts (`components/layouts/app-bootstrap.blade.php`, `layouts/app.blade.php`) load their `@vite` js entry in `<head>` too. **Projects that copied a theme must move `@themeScripts` into `<head>` in their own `synapps/resources/themes/*/layouts`** — see UPGRADE.md (v3.6.1).
- `synapse-rte.js` stub registered `synapseRte` only on `alpine:init`, which does not fire after a navigate; it now registers immediately when Alpine is already running.
- `synapps:upgrade-audit` (and the standalone `tools/synapse-upgrade-audit.php`) no longer walks into excluded directories (`vendor`, `node_modules`, `storage`, `bootstrap`, `.git`) before discarding their files — they are skipped before being opened, which makes the audit much faster on real projects — and a directory it cannot open (unreadable, or deleted mid-scan) is skipped instead of aborting the whole audit with `UnexpectedValueException`.
- Flaky tests under `pest --parallel`: `SynAppsPatchResourcesEnvTestingTest` and `SynAppsUpgradeAuditTest` now run in a private base path instead of the shared Testbench root, where other test files create and delete the same files and directories.

### Changed
- CDN scripts from `theme.json` (`js`) render with `defer`, so they keep running after the page is parsed now that `@themeScripts` sits in `<head>`.

### Added
- `synapps:upgrade-audit` / `tools/synapse-upgrade-audit.php` rule F (manual review): `@themeScripts(...)` rendered after `</head>` in a layout. `UpgradeRule::matchingLines()` (default in `AbstractUpgradeRule`) lets a rule look at the whole file instead of one line.

## [3.6.0] - 2026-10-21

### Security
- `WithDeleteToken` binds a token to the model class that issued it (`{class}|{id}|{salt}`): `Model::validateDeleteToken()` now refuses a token from an unrelated model with the same id, while a subclass token still validates on its parent (e.g. the app's `User` on `synapps-auth`'s `User`). Public API unchanged; tokens rendered before the upgrade fail once until the page is reloaded.
- Delete tokens are now stable per record within a session (AES-256-CBC with an HMAC-derived IV and an HMAC-SHA256 tag, url-safe base64, keys derived from `APP_KEY`) instead of `Crypt::encryptString()`'s random IV, so they can be used as bulk-selection checkbox values. Tokens rendered before the upgrade fail once until the page is reloaded.

### Added
- `WithDeleteToken::validateDeleteTokens(array $tokens)`: validates a bulk selection all-or-nothing — the deduplicated ids, or `false` if any token is invalid.

## [3.5.0] - 2026-10-15

### Added
- `SynAppsSetup::DEFAULT_CSP`: a new `apps.json` now allows the external hosts the bundled stubs load — Google Fonts (`style_src` `https://fonts.googleapis.com`, `font_src` `https://fonts.gstatic.com`, used by the stub themes) and the toastr notification sound (`media_src` `https://res.cloudinary.com`). Before, the stub themes' Outfit font was blocked by the default CSP.

### Changed
- `synapps:setup` writes all seven CSP directives (`frame_src` and `media_src` were missing); `synapps:reconfig` falls back to the same defaults when the project has no `csp` block.

## [3.4.6] - 2026-10-15

### Fixed
- Module seeders (`synapse.seed_path.general`) now run in module `load_priority` order (default 99) instead of filesystem scan order, so e.g. `synapps-auth` (priority 0) seeds before the modules that depend on it.

## [3.4.5] - 2026-10-15

### Fixed
- Module seeders run through the `DatabaseSeeder` hook (`synapse.seed_path.general`) failed with `Too few arguments to function ...::run()` when `run()` type-hinted dependencies: the hook fired before `db:seed` set the container, so seeders were built without injection. The hook now sets the container first.

## [3.4.4] - 2026-10-15

### Fixed
- `synapps:make-theme` crashed with a `TypeError` (an `SplFileInfo` passed to `copy()` under `strict_types`); it now copies the source theme's files.
- `synapps:vendor-fonts` vendored only the first Google Fonts link but removed every Google Fonts entry from `theme.json`, so the other fonts were lost. It now downloads every `fonts.googleapis.com/css`/`css2` link across all layouts into one `fonts.css`.

## [3.4.3] - 2026-10-15

### Changed
- `synapps:setup` / `synapps:reconfig`: with multi-language off, `enabled_languages` is now the app locale (`config('app.locale')`, i.e. `APP_LOCALE`) instead of a hardcoded `en`; an empty locale still falls back to `en`. With multi-language on, `synapps:setup` pre-fills the language list with the app locale too.

## [3.4.2] - 2026-10-15

### Added
- `synapps:remove-module --force` removes the module without asking; `--no-interaction` (`-n`) now does the same instead of cancelling.
- `synapps:patch tests` writes a generated `APP_KEY` into a new `.env.testing`, and fills an empty `APP_KEY=` (or adds a missing one) in an existing file. A non-empty key is left alone.
- Home module stub: Indonesian (`id`) translations for `menu` and `acl` ("Dasbor"), so the dashboard menu item and breadcrumb are no longer shown as the raw `home::menu.be.index` key. Existing hosts can copy `stubs/synapps/modules/home/lang/id` into `synapps/modules/home/lang/`.
- The stub language parity test now also covers the home module.
- `lang/id.json`: Indonesian for the framework strings printed in notification mail (the "trouble clicking the button" subcopy, "All rights reserved.", default greeting and salutation), so an Indonesian email no longer ends with English lines. A host's own `lang/id.json` still wins.

### Changed
- `synapps:patch tests` only adds the vendor package test paths (`vendor/*/synmod-*`, `synmodule-*`, `synapps-*`) to `tests/Pest.php` when that folder exists, and only adds the `phpunit.xml` `Packages` suite (with just the existing folders) when at least one exists. Re-run it after installing packages to add them.
- Default theme: a plain `.btn` (no colour variant) now has a gray border and a faint fill (dark mode included), so secondary actions such as Cancel no longer blend into the panel. Coloured variants, bare `<button>`s and `.input-group-item` overlay buttons are unchanged. Existing hosts can re-copy the theme (`synapps:copy-themes`) or copy the `.btn:where(:not(...))` rule into their `theme-base.css`.

### Fixed
- App URL doubled its scheme (`http://http://…`) when the `apps.json` dev/prod domain was entered with `http://` or `https://` in `synapps:setup` / `synapps:reconfig`, as the prompt asks. `overrideAppUrl()` now keeps a scheme that is already there, and only adds one to a bare domain.
- `synapps:remove-module` rejects a module name containing a path separator, so it can no longer reach a folder outside `synapps/modules`.
- Links in queued mail (and anything else built by a queue worker, the scheduler or artisan) used the `.env` `APP_URL` instead of the `apps.json` domain: the console request was created before `overrideAppUrl()` ran. It is now rebuilt from the overridden `app.url`.
- Default theme: `theme-base.css` no longer strips the right border and radius from inputs inside `.input-group`. Every `.input-group-item` is an absolute overlay, so icon, prefix, datepicker and button inputs were left without a right edge. Existing hosts can re-copy the theme (`synapps:copy-themes`) or delete the three `border-*-right*` declarations from their copy.

## [3.4.1] - 2026-10-13

### Added
- Settings module stub: Indonesian (`id`) translations for `acl`, `menu` and `page`, so a freshly set-up host has them. Existing hosts can copy `stubs/modules/settings/lang/id` into `synapps/modules/settings/lang/`.
- `SettingsStubLangParityTest` — fails when an English key of the settings stub has no Indonesian line or the `:placeholders` differ.

### Fixed
- `composer.json` branch alias: `dev-master` is now `3.4.x-dev` (was `3.0.x-dev`), so a dev checkout satisfies the `^3.3` / `^3.4` constraints of dependent packages.

## [3.4.0] - 2026-10-13

### Added
- Optional rich-text editor: `<x-synapse-rte>` (Jodit) and `VmEngine\Synapse\Rte\RichText::clean()`. Off by default; `synapps:setup` / `synapps:reconfig` ask, add `jodit` and `mews/purifier` to the `apps.json` dependencies and import `synapps/resources/js/synapse-rte.js` into `resources/js/app.js`. While off, the tag shows an error above a plain textarea bound to the same property, and the sanitiser falls back to escaped plain text when `mews/purifier` is not installed. Modules add toolbar buttons with `window.synapseRte.extend()`. See docs/COMPONENTS.md → Rich-Text Editor.
- `synapps:reconfig --dev` writes only the answers changed during the run to `synapps/config/apps.dev.json` (merged into what is already there) and leaves `apps.json` untouched.
- `VmEngine\Synapse\Services\ConfigDiff` — recursive diff/merge helpers for the `*.json` + `*.dev.json` config pairs.
- Menu badges: a backend menu item can name a badge class in `module.json` (`"badge"`). Extend `VmEngine\Synapse\Nav\NavBadge` (`count()`, `refreshOn()`, `ttl()`, `scope()`); counts are cached and refreshed by the declared models/events or with `synav()->refreshBadge()` / `refreshBadges()`. Parents sum their visible children. Rendered by `<x-synapse-nav-badge>`; `synapps:patch layouts` adds it to an existing host's nav views. See README → Menu badges.

### Changed
- Tenancy config: a module's replicable models are now read from `tenancy.replicable_models` in its own `module.json` and merged into `synapps.tenancy.replicable_models`. The separate per-module `tenancy.json` file is no longer read.
- Dev config overlays (`apps.dev.json`, `auth.dev.json`) now replace lists instead of merging them by index, so a dev override can shorten or empty a base list (e.g. `security.ip_whitelist`). Previously a dev list shorter than the base list kept the base list's trailing entries.

### Fixed
- `AutoRegistersComponents`: Livewire components of a package module whose provider registers before `SynapseServiceProvider` were never found (`Unable to find component`), because Synapse replaces the Livewire finder during its own registration. The namespace is now applied to whichever finder ends up bound.
- Tenancy config never reached the app: the builder removed the `tenancy` section from the compiled `synapps.json`, so `config('synapps.tenancy.*')` was always `null` even after `tenancy:setup`. It is now kept (and the unused `compiled/tenancy.json` is no longer written).
- The backend prefix added to `synapps.tenancy.excluded_prefixes` kept its leading slash (`/apps`) and so never matched a path segment.
- Module stubs: the todos list view used the unregistered `<x-adv-select>` (now `<x-synapse-adv-select>`), and the home module's config test asserted a `compiled/checksum` file that is no longer generated.

## [3.3.1] - 2026-10-08

### Fixed
- `<livewire:synotif::list />` (embedded in every app layout via `<x-synapse::notification-panel />`) crashed with `no such table: notifications` when in-app notifications were disabled during `synapps:setup`: its disabled branch still ran a `paginate()` count against the never-created table. It no longer queries anything while disabled.

### Documentation
- Install snippets (`README.md`, `docs/INSTALLATION.md`) required `vm-engine/synapse` `^2.4`; now `^3.3`.
- `CLAUDE.md` / `docs/MODULES.md` / `docs/THEMES.md` / `docs/COMMANDS.md` described backend routes as a hardcoded `/admin/*`. `routes/web.backend.php` is registered by synapps-auth under `{backend_prefix}/{module url_prefix}` (default `/apps`, set via `synauth:install` / `synauth:config`), named `{backend_name_prefix}.{module id}.*`.

## [3.3.0] - 2026-10-07

### Added
- IP whitelist/blacklist: global `IpFilter` middleware reads `security.ip_whitelist` / `security.ip_blacklist` (IPs or CIDR, v4/v6) from `synapps/config/apps.json`. Both empty = allow all; a blacklisted IP gets 403; when the whitelist is set, only listed IPs pass; blacklist wins. `synapps:setup` / `synapps:reconfig` prompt for both. Behind a proxy/load balancer, configure TrustProxies so `$request->ip()` is the real client.
- `ReceivesImportTask` (Excel import contract): a `ProcessesImport` processor that also implements it gets its `ExcelImport` task via `setImportTask()` before the first chunk — for row numbers, acting as `user_id`, or a per-import report in `options`. Existing processors are unaffected.

### Fixed
- `<x-synapse-modal>` focuses its first `[autofocus]` field when it opens (HTML `autofocus` only fires on page load, and CSP Alpine attributes can't hold a `$nextTick(() => …)` callback). **Existing projects:** re-copy `synapse-components.js` (`php artisan synapps:copy-resources`) and rebuild.
- `<x-synapse-datepicker>` now honours `wire:model.live` (entangles live, so filters update immediately) and renders without an empty `$wire.entangle('')` when no `wire:model` is given.
- `<x-synapse-split-pane>`: the mobile panel toggle stayed visible on desktop (the theme's `button[type="button"]` display out-ranked `lg:hidden`), taking a grid column and squeezing the aside to the handle width. The hiding rule now out-ranks it. **Existing projects:** re-copy `synapse-components.css` (`php artisan synapps:copy-resources`) and rebuild.

## [3.2.0] - 2026-10-06

### Added
- `<x-synapse-copy-button>` — clipboard copy with icon/`aria-live` feedback and an `execCommand` fallback for non-secure origins. `synapse-components.js`: `synapseCopyButton`; `synapse-components.css`: `.syn-copy-button*`.

### Fixed
- Default theme `theme-app.js` smooth-scroll handler threw `'#' is not a valid selector` on every click of a bare `href="#"` link (e.g. `<x-synapse-sort-icon>` in any sortable list); invalid anchor selectors are now ignored.

## [3.1.0] - 2026-10-06

### Added
- `<x-synapse-color-picker>` — native color input + hex field + preset swatches, entangled to `wire:model` (`#rrggbb`, validate with `hex_color`).
- `<x-synapse-steps>` — server-driven wizard step indicator; completed steps call an optional `go-method`.
- `<x-synapse-split-pane>` — aside/main layout with an optional resizable handle (width remembered per browser via `storage-key`) and a mobile aside toggle.
- `<x-synapse-kanban>` / `.column` / `.card` — board layout on Livewire 4's native `wire:sort` groups (`sortMethod($id, $position, $destinationColumnId)`).
- `<x-synapse-calendar>` — month grid + mobile agenda built server-side; `nav-method`, `event-method`, `day-method` callbacks, Monday/Sunday week start, "+N more" overflow.
- `SanitizesWireArguments` component concern: method-name and key whitelists for values interpolated into `wire:*` expressions.
- `synapse-components.js`: `synapseColorPicker`, `synapseSplitPane`. `synapse-components.css`: `.syn-color-*`, `.syn-step*`, `.syn-split-pane*`, `.syn-kanban*`, `.syn-calendar*`.

### Documentation
- README "Blade Components", `docs/COMPONENTS.md` (Color Picker + new "Planning & Layout Components" section), `UPGRADE.md` "Upgrading to v3.1.0" (re-copy the `synapse-components.js`/`.css` stubs), package `CLAUDE.md` component list.

## [3.0.0] - 2026-10-06

Includes everything through [2.5.2] (`SsrfGuard`, `SafeOutboundUrlRule`, `security.ssrf_allow_loopback`), merged from the 2.x line. Upgrading from 2.x: see [UPGRADE.md](UPGRADE.md).

### Added
- **`synapse-components.js`** — all component `Alpine.data()` registrations (adv-select, confirm-dialog, datepicker, drag-n-drop, file-drop, lightbox, modal, password-generator, search palette, toastr, toggler) moved out of per-view inline `<script>`s into one file imported by `synapps.js`, like `synapse-components.css`. Inline scripts never ran when a component first appeared through a Livewire morph (e.g. status togglers after leaving a trash view). Blade values are passed through `x-data` arguments. `synapps:patch` installs the file and the import in existing projects.
- `RemembersQueryParams` now reflects session-restored values in the address bar (browser event + `history.replaceState`), honouring `#[Url(as:)]` aliases.
- `.syn-diff-table` / `.syn-diff-pre` / `.syn-diff-empty` CSS primitives (Field / Before / After change tables).
- `<x-synapse-drawer>` closes on Escape.
- Impersonation banner: the header stub renders `<x-synauth::impersonation-banner />` and hides the profile link while a dev is impersonating (`@unlessimpersonating`); the frontend layout stubs (`layouts/layout`, `themes/default` and `themes/application` `frontend`) `@includeIf` the banner, so they still render without synapps-auth.
- **Global search (opt-in).** `searchables` table, `SynSearchable` facade / `synsearchable()` helper (`put`, `forget`, `forgetSource`, `search`), `synapps:searchable-setup` (imports backend nav + module.json `searchables`, per enabled language) and `synapps:searchable-forget`. Ctrl/⌘+K command palette `<livewire:synsearch::palette />` mounted in the header stub. Eloquent search by default; optional Laravel Scout engine via `synapps.apps.searchable.engine = scout`.
- Semantic CSS primitives from the synmod-pm pass (`synapse-components.css` + stub): `.syn-row-muted` (inline-add/sub rows in editable datatables), `.syn-field-caption` (tiny uppercase caption in dense cells), `.syn-media-card` + `-img`/`-placeholder`/`-body`/`-title`/`-actions` (image-header cards), `.syn-icon-dropdown` / `.syn-icon-dropdown-item` (teleported row-action menu; item selector outranks theme-base's `button[type="button"]` reset).
- `<x-synapse-lightbox>` — full-screen image preview, opened via the window event `open-lightbox-{name}` with detail `{ url, alt? }` (closes on backdrop click, close button, Escape, or `close-lightbox-{name}`); styled by new `.syn-lightbox*` classes. Extracted from synmod-fm's hand-rolled preview overlay.
- `stubs/synapps/resources/css/synapse-components.css`: `.syn-modal-header`/`-title`/`-close`/`-body`/`-footer` (content helpers for `<x-synapse-modal>`'s unstyled slot), `.form-help`, `.syn-breadcrumb-link`/`-sep`, `.syn-section-label`, `.syn-folder-chip` (+ `-sm`, `-icon`), `.syn-empty-state` (+ `-icon`, `-text`), `.syn-drop-overlay` (+ `-icon`, `-text`), `.syn-segmented` (+ `-item`, `-item-active`), `.syn-selection-bar-card`/`-count`, `.syn-row-selected`, `.syn-file-tile*`, `.syn-file-thumb-sm`/`.syn-file-icon-sm`, `.syn-sort-header` — extracted from synmod-fm's semantic CSS pass.
- `stubs/synapps/resources/css/synapse-components.css`: `.syn-tabs`/`.syn-tab`/`.syn-tab-active` (underline tab nav), `.syn-panel-body`/`.syn-panel-footer` (divider helpers for content inside `<x-synapse-panel>`'s unstyled body slot), `.syn-icon-badge` + `-warning`/`-success`/`-danger`/`-primary` (circular icon badge beside a modal/notice heading), `.syn-secret-reveal`/`.syn-secret-reveal-copy` (one-time secret/token reveal box with a pinned copy button), `.syn-status-row` (label/status + action row on a muted background) — extracted from synapps-auth's semantic CSS migration as genuinely reusable primitives.
- `stubs/themes/default/css/theme-base.css`: `.card-title` — was referenced (unstyled) by existing views but never defined.
- `synapps:remove-module {moduleName} [--permanent]` — safely removes a directory-based module. By default moves it to `.trash/{moduleName}-{timestamp}` in the project root (added to `.gitignore` automatically); `--permanent` deletes it outright. Always asks for confirmation, then runs `synapps:discover` to clean up `composer.json`'s autoload entries and regenerate compiled configs.
- **Phosphor Icons** (`@phosphor-icons/web`, `regular` weight, loaded from `node_modules` via `@import "@phosphor-icons/web/regular";` in `synapps.css`) — now the recommended icon set, wired into `synapps:setup`/`synapps:reconfig`'s default `apps.json` dependencies. FontAwesome remains installed and fully supported as an alternative; see the new "Icons" section in [README.md](README.md#icons) for guidance, including a documented gotcha where most FontAwesome Free icons only have a glyph in the `fa-solid` style (non-solid styles render nothing for the vast majority of icons in the free tier).
- `<x-synapse-badge>` — pill-shaped status badge with color variants (`gray`, `brand`, `success`, `warning`, `danger`, `info`), two sizes, and an optional icon.
- `<x-synapse-panel>` — card-shell wrapper for list/form pages (title + optional `toolbar` slot), replacing the hand-rolled `rounded-2xl border ... dark:bg-white/3` markup duplicated across list/form views. Supports `collapsible`/`collapsed` for a click-to-toggle body using the existing `@alpinejs/collapse` plugin.
- `<x-synapse-search-box>` — text input with a left-aligned search icon and an optional `trailing` slot (e.g. a filter-drawer toggle button); all other attributes (`wire:model`, `id`, ...) pass through to the `<input>`.
- `<x-synapse-stat-tile>` — single-metric card component. `variant="tile"` (default): icon square + label/value, with a `bordered` toggle for the plain flat "quick stats" look. `variant="card"`: title-row layout with `iconPosition` (`left`/`title`/`background`), optional whole-card `tinted` color wash, `sublabel`, and `href`/`clickable` affordance. `label`/`value`/`sublabel` can be overridden with matching named slots for rich content. `iconBackground` (both variants) toggles the colored icon square/circle off for a bare icon glyph.
- `<x-synapse-excel-export-button>` — reusable button for `ExcelExporter` exports. `mode="sync"` renders a simple wire:loading spinner-swap button for exports that return a download response directly. `mode="async"` renders the full background-job pattern (processing state with a live progress bar driven by the export task's `processed_rows`/`total_rows`, completed/download state, failed state) — pair it with the new `WithExcelExport` trait, which gives a host Livewire component `excelExportTask()`, `downloadExcelExportFile()`, and `dismissExcelExportTask()` helpers so it doesn't have to reimplement `ExcelExport` lookup/download/cleanup. All labels are optional props with plain-English defaults.
- `stubs/synapps/resources/css/synapse-components.css`: `.syn-selection-bar` (bulk-action bar above a selected datatable, recurs identically in synmod-queue's list views), `.form-label`/`.form-error` (flat form-label/error styling for synmod-cms's wrapper-less convention), and `.syn-form-action-bar` (fixed bottom save/cancel bar recurs identically in synmod-cms's page/article forms) — extracted from those modules' semantic CSS passes as reusable primitives.
- Module migrations under `database/migrations/central/` are now included in `migration_path` when the project has tenancy enabled (`synapps/config/tenancy.json` present).
- `synapps:make {object} {module} {name?}` — unified command to create a component for a `synapps/modules` module, replacing the 34 individually-listed `synapps:make-*` generator commands as the documented primary entry point. `name` is required for every type except `module` (which has no separate name to give).
- In-app notification system built on Laravel's stock `notifications` table (no schema changes): a `Notify` facade for targeted sending (specific users, roles-with-exceptions via `vm-engine/synapps-auth`, optional background dispatch via `->queued()`), a `synotif:setup` command (plus matching prompts added to `synapps:setup`/`synapps:reconfig`), and `<livewire:synotif::bell />` / `<x-synapse::notification-panel />` components — a badge/dropdown with toastr popups and a paginated "view all" modal, both auto-marking read on view while staying visually unread until reopened. Title/message render as translated strings (`__()`) at display time from a stored translation key + params. Disabled by default; gated on a config flag plus the `notifications` table actually existing, no-op (optionally logged) otherwise.
- Test coverage for the welcome-route detection fix (`tests/Feature/Commands/SynAppsPatchResourcesWebRoutesTest.php`).
- Test coverage for the automatic `livewire.csp_safe` config override (`tests/Unit/SynapseServiceProviderCspTest.php`).
- `synmod-make class <module> <name>` now dispatches to `synapps:make-class`, joining the rest of the unified generator menu (was already implemented as a standalone command but missing from `synmod-make`'s type map).
- Theme frontend layout contract (`default` and `application` themes): `$description` meta override (`->layoutData(['description' => …])`), `@stack('head')` for hreflang/OG/canonical tags and `@stack('nav')` for header navigation; translatable UI strings (`__('Sign in')` etc.).
- `frontendHeader` Alpine component in both themes' `theme-frontend.js` (scroll shadow + mobile menu with `aria-expanded`/Escape) and styled `fe-*` shell CSS — the frontend layout's classes previously had no styles at all.
- `tests/Unit/Themes/ThemeStubsTest.php` — contract tests across both bundled themes (no `@vite`, fonts only via theme.json, no inline `Alpine.data`, no core `synapse*` Alpine names, frontend contract, guest render).

### Changed
- `.syn-form-action-bar` is now `sticky` inside the form instead of `fixed` to the viewport, so the admin sidebar no longer covers its left-hand (Back) button; rounded top + border to sit inside the content column. Pages using it no longer need a bottom spacer. Existing projects: copy the updated rule from the stub into `synapps/resources/css/synapse-components.css` (`synapps:patch` only installs the file when missing).
- **PHPStan level 7 with a minimal ignore list.** Dropped the blanket `ignoreErrors` patterns; only three path-scoped ignores remain (intentional `env()` in `ConfigGenerator\Builder`, optional synapps-auth `Role` in `Notify`, library traits unused in-package). New `phpstan-bootstrap.php` registers the `synapse::` view namespace in Larastan's bare Testbench app so `view()` view-string checks resolve.
- **Excel import rows typed `array<array-key, mixed>`** (was `array<string, mixed>`) across `ColumnMapper::map()`, `ExcelImporter`, `ChunkIterator`, `ProcessExcelImport` and the `ProcessesImport` contract — rows really are 0-indexed when no column mapping is set. Docblock-only; no runtime change.
- `SynapseServiceProvider` references the app's `Database\Seeders\DatabaseSeeder` by string instead of importing a class the package doesn't ship.
- Added `pestphp/pest-plugin-phpstan` dev dependency.
- `<x-adv-select>` reworked: the search input now lives inside the dropdown instead of nested inside the bordered main box (fixing a double-border look under themes that border both the box and the input), gained a rotating dropdown-arrow indicator, and its clear/pill-remove buttons now use Phosphor icons instead of a bare `✕` glyph.
- `<x-adv-select>` gained a `disabled` prop (native `disabled` on the search input and clear/remove buttons, an `is-disabled` CSS class dimming the box, `focusInput()` a no-op while disabled) — needed to migrate fields off the removed `select`/`multi-select` components that relied on their `disabled` prop.
- All FontAwesome usages inside the package itself (`badge`, `panel`, `search-box`, `file-drop`, `drawer`, `adv-select`, `sort-icon`, `password-generator`, `confirm-dialog`, `stat-tile` doc examples, `Alert`'s auto-assigned icons, `ConfirmDialog`'s default icon) were converted to Phosphor. A new `.syn-spin` CSS keyframe utility replaces FontAwesome's built-in `fa-spin`, which Phosphor doesn't ship (paired with `ph-spinner-gap` for loading spinners). FontAwesome usages in other packages/modules are unaffected and not yet migrated.
- Moved `.datatable` styles from `theme-app.css` to `synapse-components.css`; added `.datatable-sm/-striped/-bordered/-borderless/-no-hover` variants and a CSS-only mobile card view (`data-label` populated at runtime by `initDatatableLabels()` in `synapps.js`, no Blade changes required). `.datatable-col-checkbox`/`.datatable-col-actions` mark columns for special mobile placement.
- `.syn-search-box-input` nested under `.syn-search-box` to beat `theme-base.css`'s `input[type="text"]` on specificity.
- `synapps:remove-module` hardened to pass PHPStan level 7: the required `moduleName` argument is now type-checked with `is_string()` (returns `self::INVALID` otherwise) instead of an unconditional `(string)` cast, and the `.gitignore` scan guards against `preg_split()` returning `false` (falls back to an empty line list via `?: []`).
- The 34 individual `synapps:make-*` generator commands are now hidden from `artisan list` (still fully functional, both directly and via `synapps:make`/`synmod-make`).
- `synmod-make` gained the two component types (`exception`, `module`) it was previously missing from its dispatch map.
- **`synapps:patch-resources` replaced by `synapps:patch {object?*}`** — option flags (`--theme`, `--comment-default`, `--css-sources`, `--npm-watch`, `--js-back`, `--vite`, `--split-layouts`) are now positional object arguments (`theme`, `seeder`, `css`, `npm`, `js`, `vite`, `layouts`), e.g. `php artisan synapps:patch layouts`. Omit the argument to patch everything, as before.
- **`synapps:patch-tests` folded into `synapps:patch`** as the new `tests` object — testing infrastructure (`tests/Pest.php`, `.env.testing`, composer's test script, `phpunit.xml`) is now configured by `synapps:patch` (or `synapps:patch tests` to run just that step) instead of a separate command. `synapps:setup` no longer calls `synapps:patch-tests` separately.
- **PHPStan raised from level 6 to level 7** — fixed all resulting findings across the package (nullable/false return narrowing on `file_get_contents()`, `glob()`, `realpath()`, `json_encode()`, `DOMXPath::query()`, and Artisan `argument()`/`option()` mixed-type reads). Added `Traits\HasTypedInput` (`stringArgument()`/`stringOption()`) shared across Artisan commands for type-safe input access.
- **Requires PHP ^8.5 and Laravel ^13.0** (dropped `12.x|13.x` dual support) — dev dependencies bumped accordingly: Pest ^5.0, Pest Plugin Laravel ^5.0, Orchestra Testbench ^11.0, LaraDumps ^5.0.
- **Alpine.js is now CSP-safe by default.** `SynapseServiceProvider` auto-forces `config('livewire.csp_safe') = true` on boot (`overrideLivewireCsp()`), so every Synapse-based project ships with Livewire's CSP-safe Alpine build without any manual config edit. `SecureHeaders`'s `script-src` directive no longer includes `unsafe-eval`.
- All bundled Blade components (`modal`, `confirm-dialog`, `drag-n-drop`, `file-drop`, `password-generator`, `adv-select`, `toastr`, `datepicker`, `toggler`, `currency`, `multi-select`) and stub layouts/error pages were migrated off inline `x-data="{ ... }"` object literals with methods/arrow-functions, multi-statement directive expressions, and `@entangle()` to the CSP-safe `Alpine.data()` registry pattern, guarded against the `alpine:init`-timing race (SPA `wire:navigate` firing after Alpine has already started) via a self-registering IIFE.
- `multi-select.blade.php` was previously an unregistered bare global `function dropdown() {...}` referenced from `x-data` — never CSP-safe and silently broken under any Alpine.js version that enforces the CSP grammar. Rewritten as a properly registered, uniquely-named `synapseMultiSelect` factory (renamed to avoid colliding with the unrelated `dropdown` component shipped in the default/application theme's `theme-base.js`).
- **`declare(strict_types=1)` added across the package** (`src/`, `tests/`, `database/`, root scripts) and `phpstan.neon` bumped from level 5 to level 6, with every resulting finding fixed with real types rather than suppressed.
- Added a shared `read_json_file()` helper and switched every `json_decode(file_get_contents(...), true)` call site in config/module generation to it, so a missing or malformed JSON file now throws a clear error instead of silently producing `null`/a `TypeError` under strict types.
- Theme fonts are declared once in `theme.json` `headers.all` instead of hardcoded `<link>`s in layouts (and the application theme's CSS `@import url()`), so `synapps:vendor-fonts` can find them and fonts load once.
- **Application theme brought to parity with the default theme**: generic branding (app title/description, icon-only logos), `<x-synapse-toastr/>`, real `<livewire:synsearch::palette/>` and `<livewire:synotif::bell/>` + notification panel in the header, Alpine shells moved to theme JS as `applicationAppShell` / `applicationThemeShell` / `applicationSidebarSubmenu`, shared `darkMode` storage key, frontend layout declared in `theme.json`.
- Rewrote both themes' README to match the 4+4 split file structure and the frontend contract.

### Fixed
- **Flash toasts after a redirect never showed**: `<x-synapse-toastr>` passed session flash through `Js::from()`, which emits `JSON.parse(...)` — not available to the CSP-safe Alpine evaluator — so the whole toastr failed on any page reached with a flash message.
- Dismissing a toast threw `this.notifications.filter is not a function` (an app-registered `Alpine.data('notifications')` shadowed the toastr's array); `removeNotification` is now bound to the toastr root.
- **Notification bell/list**: templates opened with `@if`, so Livewire put the component root on an inner element — `wire:poll` refresh, "Mark all read" and item clicks were outside the component. Disabled notifications threw `RootTagMissingFromViewException`. "View all" failed the CSP parser (two statements).
- `<x-synapse-password-generator>` "set" called an undefined bare `$wire` and never set the model(s).
- `<x-synapse-confirm-dialog>` ignored the `confirmText` passed via the `confirm-dialog` event.
- `<x-synapse-datepicker>` rendered `type="date"`, so datetime values triggered browser format warnings before flatpickr took over.
- App layout: `.main-container` lacked `min-w-0`, so wide tables squeezed the sidebar.
- `ExcelImport::$status` docblock declared `string` although the column is cast to `ExcelTaskStatus`.
- Excel export contract (`ExportQuery::__invoke()`, `ExcelExporter::make()`/constructor, `ProcessExcelExport::resolveQuery()`) now types the source as `EloquentBuilder<covariant Model>`: passing a model-specific builder (e.g. `Builder<ProjectInvoice>`) was rejected by PHPStan because `Builder<TModel>` is invariant. Docblock-only.
- `<x-synapse-panel collapsible>`: the chevron wrapped below the title — `.syn-panel-title-wrap` is now `flex items-center`.
- `<x-adv-select>`: clicking the box only opened the dropdown, never closed it, and the `live` update flag referenced a stale `this.live` instead of the actual `isLive` prop — fixed click handler to `toggleOpen()` and the stale reference.
- `<x-adv-select>`'s `x-data` inlined `preload`/`options` via `@js()`, which wraps values in `JSON.parse(...)` — unavailable under Livewire's CSP-safe Alpine evaluator (no `JSON` global), silently breaking every binding. Switched to raw `json_encode()` literals.
- `theme-base.css`: duplicate `.btn` base rule (`px-4 py-3`) conflicted with the tag-aware `.btn` reset (`px-4 py-2`), so `<a class="btn">` and `<button class="btn">` rendered with different padding depending on selector specificity/source order. Removed the duplicate, keeping `text-sm`/`shadow-theme-xs`/`cursor-pointer` on the single canonical rule (also applied to the `default` theme stub).
- `theme-base.css`: `.btn-icon` had no CSS of its own, so `<button class="btn-icon">` inherited the base `.btn` padding while `<a class="btn-icon">` stayed bare, rendering the two tags at different sizes for the same class. Restored `.btn-icon` (with color variants) from `lta.css`, excluded from the tag-based reset via `:not(.btn-icon)`.
- The bundled `todos` module stub (`stubs/synapps/modules/todos/.../todo-list.blade.php`) still used the removed `<x-synapse-select>` — swapped to `<x-adv-select>`, the only package-internal reference left after the select/multi-select/select2 removal above.
- `<x-synapse-drawer>`'s close icon had a typo'd class (`fa-reguler` instead of `fa-regular`), so it never rendered under FontAwesome regardless of style — fixed as part of the Phosphor conversion above.
- `SynapseConfigGenerator::setDatabasePaths()` no longer appends the package's own `database/seeders` path when it doesn't exist, which previously polluted `seed_path` with a `false` from `realpath()`.
- `synapps:make-view` (and `synapps:make view` / `synmod-make view`) generated a Blade file with a literal, never-substituted `{{ name }}` in its doc comment — the stub referenced a `name` variable that's never passed to it. Fixed to use `{{ class }}`, the same variable the sibling `component-view.stub` already used correctly. Found via a full smoke test of every `synapps:make` component type.
- **`synapps:patch-resources` never commented out the default Laravel welcome route on newer Laravel skeletons** — `patchWebRoutes()`'s detection only matched the older `Route::get('/', function () {...})` closure form, not the `Route::view('/', 'welcome')->name('home')` form Laravel 13's skeleton uses, so the welcome route was left active. Since the `home` module intentionally also claims `GET /`, this caused a silent URI collision where the module's own named route (`home.landing`) could be evicted from the route collection, breaking any redirect targeting it. Detection now matches both forms.
- **`synapps:patch-resources`'s CSS split-layout rewrite never fixed a re-introduced stray `synapps.css` import in `app.css`** — `rewriteAppCssToFocused()` detected "already focused" by checking for a `tailwindcss` import marker instead of the direct `synapps.css` import marker (unlike its JS counterpart, which checked the right thing), so a stray import re-added by the legacy `patchAppCssImport()` step on a later run was never cleaned up.
- **`StyledWriter::writeRow()` had a tautological `elseif` branch** flagged by PHPStan level 5 — restructured into nested `if`/`else`.
- **`<x-synapse::toggler>`'s non-inline variant used the deprecated `@entangle()` Blade directive**, which compiles to `window.Livewire.find(id).entangle(prop)` — unresolvable inside a CSP-restricted directive expression since bare `window` property chains aren't reachable there. Switched to `$wire.entangle(name).live`.
- **`<x-synapse::toastr>`'s flash-notification `@notify.window` handler used optional chaining (`?.`)**, unsupported by Alpine's CSP expression parser, and its `x-init` chained four `@if` blocks in one attribute (multi-statement, also unsupported). Extracted into `handleNotifyEvent()`/`initFlashNotifications()` methods fed by a server-computed flash array.
- Stub tests (`home`/`todos` modules) updated to match already-shipped app behavior: `'datetime'` casts now correctly assert `Carbon\CarbonImmutable` (the app's `Date::use(CarbonImmutable::class)` default), the resource-patch test checks the actual per-layout split chain (`base.css`/`base.js` → `synapps-base.*` → `synapps.*`) instead of the old monolith `app.css`/`app.js` imports, Livewire component references use `::` namespace syntax instead of legacy dot notation, and the `apps.json`/`system.json` key assertion was corrected from `apps_code` to `app_code`.
- **`is_decimal()` threw a `TypeError` under strict types** — it passed a numeric string straight into `floor()`, which no longer coerces automatically. Fixed with an explicit `(float)` cast.
- **`synapps:make-observer` never emitted a `use` import for the model, and left `{{ modelImport }}`/`{{ modelVariable }}` unreplaced in some paths.** Model resolution (short name → module's own `Models` namespace, FQCN used as-is, or a fallback to the base Eloquent `Model`) is now computed centrally in `ModuleGeneratorService::prepareVariables()`, matching the pattern already used by `factory.stub`.
- `synapps:patch` now re-points `tailwind-ref.css` at the active theme, so `@apply` in layout CSS entries no longer compiles against the previous theme's tokens after a theme switch.
- Default theme frontend: mobile toggle stayed visible on desktop (theme-base's global `button[type=button]` rule out-specified it; now opts out via `btn-icon`) and the page background ignored dark mode (`.dark` sits on `<body>` itself).
- Fallback `components/layouts/app.blade.php` loaded the Outfit font twice (hardcoded links plus `@themeHeaders`).
- Application theme: module `notify` toasts were silently dropped (own `toast` component never listened), duplicated page title, mobile sidebar overlay used `window` in a template (fails the CSP-safe evaluator), Cmd/Ctrl+B swallowed the shortcut on core-layout pages, frontend layout called unguarded auth routes, assets double-loaded via `@vite()`, Font Awesome 6.5 CDN loaded on top of the bundled FA 7.
- **`$wire` assignments threw under Livewire's CSP build** (`properties[name] is not a function`), so every `#[Modelable]` child (`x-modelable="$wire.value"`, e.g. the synmod-fm file picker) logged errors and lost parent→child value updates. Alpine's CSP evaluator reads `obj.constructor` before member assignments and Livewire's `$wire` proxy treats inherited `Object.prototype` keys as aliases (still in livewire/livewire v4.4.7); `synapse-components.js` now wraps each component's `$wire` on `component.init` to answer those keys itself.

### Removed
- Static TailAdmin "Search or type command…" box from the header stub (`layouts/partials/header.blade.php`); it had no behaviour and is replaced by the global search palette.
- Write-only properties `SpoutReader::$filePath`, `StyledWriter::$sheetName`, `TemplateWriter::$sheetName`.
- FM-only CSS classes `.syn-file-tile*`, `.syn-folder-chip*`, `.syn-drop-overlay*`, `.syn-segmented*`, `.syn-file-thumb-sm`, `.syn-file-icon-sm` from `synapse-components.css` — synmod-fm now ships its own `fm.css`. Generic primitives from that pass (`.syn-breadcrumb-*`, `.syn-section-label`, `.syn-empty-state*`, `.syn-selection-bar-*`, `.syn-row-selected`, `.syn-sort-header`) stay.
- **BREAKING: `select` / `multi-select` / `select2` Blade components removed** (`<x-synapse-select>`, `<x-synapse-multiselect>`, and the third-party Select2 wrapper) in favor of `<x-adv-select>`. `select2` had no usages anywhere in the codebase. The bundled modules (`synapps-auth`, `synapps-fallback`, `synmod-cms`, `synmod-example`, `synmod-pm`) have been migrated; **Upgrade:** replace any remaining `<x-synapse-select>`/`<x-synapse-multiselect>` in your own modules with `<x-adv-select>` (see [docs/COMPONENTS.md](docs/COMPONENTS.md#advanced-select)).
- **BREAKING: `system.json` config system removed entirely.** `synapps/config/system.json` and `system.dev.json` (source), `synapps/config/compiled/system.json` (compiled output), the `System` config builder, and `config('synapps.system.*')` are all gone. It was almost entirely dead weight — nothing in the ecosystem read most of its keys (`env`, `multilang`, `locale`, `fallback_locale`, `has_auth`, `has_acl`, `force_secure`, `userfile_url`, the computed `number_format` defaults) — but it had two narrow real consumers, both removed as part of this change:
  - `SynapseServiceProvider`'s `path.public` binding, driven by `synapps.system.public_path`. Projects relying on a non-default public path must configure it another way (this feature is not being replaced).
  - `synapps:setup`'s write of the generated encryption key into `system.json`'s `laravel_key` — redundant with Laravel's own `APP_KEY` in `.env`, which is unaffected by this change.
  Also removed: `Builder/Binding.php`'s `mergeBinding()` (a per-project binding-override mechanism that only worked via a `binding` key in `system.json`, and is now unreachable), and the `allowed_dev_keys` list in `config.json` (only ever read by the deleted `System` builder).
  **Upgrade:** delete `synapps/config/system.json`, `synapps/config/system.dev.json`, and `synapps/config/compiled/system.json` from existing projects, then run `php artisan synapps:config` to regenerate. If you relied on `synapps.system.public_path` to override the app's public directory, that override is gone — remove it from your setup or reintroduce it yourself via a service provider.
- Application theme: unused `window.m4vm` utility namespace, `m4vm` Alpine store and fake `search`/`sidebarMenu` components, the theme's own `toast` component, the dummy header search input and hardcoded sample notifications, and the Cmd/Ctrl+D shortcut (hijacked the browser bookmark shortcut).

## [2.5.2] - 2026-08-31

### Added
- **`SsrfGuard` loopback bypass for local development** — a new `security.ssrf_allow_loopback` block in `synapps/config/apps.json` (`config('synapps.apps.security')`, default `false`) lets `SsrfGuard::isSafeUrl()` accept `127.0.0.0/8`/`::1` when enabled, for pointing a sandbox `base_url` at `127.0.0.1` without a tunnel. Never honored when `app()->environment('production')`, regardless of the config value. `synapps:reconfig` preserves this block on every run and `synapps:setup` scaffolds it with its default, matching the existing `csp`/`logging` convention.

## [2.5.1] - 2026-08-26

### Fixed
- **`SsrfGuard` rejected legitimate RFC1918/ULA private-network endpoints** — bank sandbox/production APIs are commonly reachable only via VPN/leased-line on a private IP (e.g. `10.0.119.14`), but `isPublicIp()` blocked all private ranges outright, making it impossible to save such a provider URL at all. It now only rejects loopback, link-local (including the `169.254.169.254` cloud metadata address), and other non-private reserved ranges; RFC1918/ULA private addresses are allowed.

## [2.5.0] - 2026-08-26

### Added
- **`VmEngine\Synapse\Services\Security\SsrfGuard`** — validates that a URL is safe for the application to fetch server-side, rejecting non-http(s) schemes and any host (literal IP or resolved hostname) that is loopback, RFC1918-private, link-local, or otherwise reserved (including the `169.254.169.254` cloud metadata address).
- **`VmEngine\Synapse\Rules\SafeOutboundUrlRule`** — a `ValidationRule` wrapper around `SsrfGuard::isSafeUrl()` for validating user-supplied URLs (e.g. webhook/provider base URL config fields) at save time.

## [2.4.13] - 2026-08-12

### Fixed
- **`<x-synapse::modal>` content taller than the viewport was unreachable** — the modal box capped itself at `max-h-[calc(100vh-2rem)]` and laid out as `flex flex-col`, but neither it nor its backdrop wrapper set `overflow-y-auto`, so any modal whose content exceeded the available height (e.g. multi-step forms, code/QR display grids) simply overflowed past the box with no scrollbar and no way to reach the clipped content. Added `overflow-y-auto` to the modal box so tall content scrolls within it instead of being clipped.

## [2.4.12] - 2026-08-06

### Fixed
- **Livewire's `@livewireScripts`/`@livewireStyles` inline tags were blocked by CSP** — Livewire defaults their nonce to `Vite::cspNonce()`, which independently generates its own random nonce the first time it's called if nothing registered one first. Since `SecureHeaders` never told Vite about the CSP nonce it puts in the `Content-Security-Policy` header, the two never matched, and every request produced spurious CSP violations for Livewire's own inline bootstrap `<script>`/`<style>` tags — visible in the browser console as unattributed "blocked an inline script/style... consider using a hash or a nonce" errors with no matching source in the app's own Blade files. `SecureHeaders` now calls `Vite::useCspNonce($nonce)` with the same nonce used in the header, so Livewire's (and any `@vite()`-based) inline tags carry a matching nonce.

## [2.4.11] - 2026-08-06

### Added
- **`SecureHeaders` CSP config now supports `frame_src` and `media_src`** — the `csp` block in `synapps/config/apps.json` (`config('synapps.apps.csp')`) previously only extended `script_src`, `style_src`, `font_src`, `img_src`, and `connect_src`; `frame-src` was hardcoded to `'self'` and `media-src` wasn't set at all (silently falling back to `default-src 'self' data:`). Both are now config-driven like the other five directives, needed for embeds like reCAPTCHA iframes (`frame_src`) or CDN-hosted audio/video (`media_src`).
- **`NoCacheHeaders` middleware** (`no-cache` alias) — sets `Cache-Control: no-store, no-cache, must-revalidate, max-age=0`, `Pragma: no-cache`, and `Expires: 0` on the response, for routes serving sensitive data that must never be cached (e.g. by the browser back/forward cache).
- **Abort at boot if `APP_DEBUG` is enabled in `production`** — `SynapseServiceProvider` now raises a 500 during boot when `app.debug` is true in the `production` environment, preventing stack traces and environment details from leaking to end users.

### Fixed
- **`synapps:reconfig`'s scaffolded `glob` npm dependency moved from `main` to `dev` at `^13.0.0`** (was `^11.0.0` under `dev`, and duplicated under `main`) — `glob` is a build-time tool used by `vite.config.js`, not a runtime dependency, matching how `synapps:setup` declares it.

## [2.4.10] - 2026-07-31

### Fixed
- **Tailwind v4 `@apply` had no theme context in split entry CSS files** — `resources/css/{app,frontend,guest}.css` are independent Vite entries that never pulled in `lta.css`'s custom `--color-brand-*` tokens (only reachable via the `base.css` chain), so `@apply text-brand-500` etc. in theme CSS (`theme-app.css`) silently failed to resolve. `synapps:patch-resources --split-layouts` now also generates `synapps/resources/css/tailwind-ref.css` (a minimal `@import "tailwindcss"` + theme-token reference file with no `@source` directives, so it doesn't trigger redundant blade/js scanning) and prepends `@reference '../../synapps/resources/css/tailwind-ref.css';` to the generated `app.css`/`frontend.css`/`guest.css`.
- **`stubs/synapps/resources/views/components/layouts/nav/menu-item.blade.php` used the wrong component path** — `<x-synapps::nav.arrow>`/`<x-synapps::nav.submenu>` were missing the `layouts.` namespace segment used by every sibling file in the same directory (`<x-synapps::layouts.nav.arrow>`/`<x-synapps::layouts.nav.submenu>`), which resolve to `resources/views/components/layouts/nav/*.blade.php`.
- **Default frontend theme layout assumed modules that don't exist in a fresh scaffold** — `stubs/themes/default/layouts/frontend.blade.php` unconditionally called `<x-cms::menu>` (no `cms` module ships by default) and `route('backend.home.dashboard')` / `route('auth.login')` (assume the `home` module and `vm-engine/synapps-auth` are installed). The CMS menu call was removed, and both route links are now wrapped in `@if (Route::has(...))` guards so the stub degrades gracefully on a bare `synapps:setup` scaffold.

## [2.4.9] - 2026-07-30

### Changed
- **Default stub theme reworked to a TailAdmin-faithful design** — white/near-black sidebar, TailAdmin's real brand color tokens (`#465fff`), dark mode via a `.dark` class toggle instead of `@media (prefers-color-scheme: dark)`, and a large cleanup of unused/duplicated `theme-*`-prefixed CSS classes in `theme-app.css`/`theme-base.css`. New `logo-icon.svg` asset added.
- **`stubs/synapps/resources/views/components/layouts/app.blade.php`** — now renders theme CDN headers/styles/scripts via `@themeHeaders('app')`/`@themeStyles('app')`/`@themeScripts('app')` instead of a bare `@vite(['resources/css/app.css'])` call, so the stub layout picks up `ThemeService`-driven CDN resources and the base+app Vite bundle split.
- **`stubs/modules/settings/routes/web.backend.php`** — `Route::livewire()` targets updated from dot-notation (`settings.configuration-list`) to namespace notation (`settings::configuration-list`), matching the current Livewire v4 component registration format.

### Fixed
- **`ThemeServiceTest` — 3 new fallback-tier tests relied on a theme directory that doesn't exist in the test environment** — `resolveThemeLayout()`'s middle fallback tier checks a theme's layout files on disk, which real installs get via `synapps:setup`'s `vendor:publish`, but Testbench's bare base path has none. Tests now create a dedicated fixture theme directory in `beforeEach` rather than assuming the "default" theme's files are present (a name reused by other test files' own disk fixtures, which raced under `--parallel`).

## [2.4.8] - 2026-07-20

### Added
- **`synapps:upgrade-audit` command** — detects legacy `vm-engine/synapse` patterns that need updating (Blade `<livewire:module.name>` dot-notation, `Route::livewire()` dotted string targets, removed `registerMfcComponents()`/`registerLivewireComponents()` calls, Livewire v3 `Livewire.hook()` JS calls, `Route::get(Component::class)` candidates). Reports only by default; pass `--apply` to auto-fix the mechanical findings (with a `{file}.bak` backup and a git-clean check first).
- **Standalone `tools/synapse-upgrade-audit.php` script** — a self-contained, zero-dependency version of the same audit for projects still on an old synapse version that don't have the artisan command available yet. Copy it into the target project's root and run `php synapse-upgrade-audit.php` directly.

### Fixed
- **`UpgradeAuditService` — crash on a file vanishing mid-scan** — `scan()`/`apply()` now check `is_readable()` and suppress `file_get_contents()` warnings before reading, instead of letting a race between directory listing and reading throw an uncaught error.

## [2.4.7] - 2026-07-17

### Fixed
- **`synapps:reconfig` — `themes` block reset on every run** — was hardcoded to `active: 'default'`/empty `css`/`js`/`headers`, wiping out any theme customization on each reconfig. Now reads `config('synapps.apps.themes', [...])` first, matching the existing `csp`/`logging` preservation pattern.
- **`synapps:reconfig` — `dependencies` block reset on every run** — same issue: hardcoded npm/composer baseline overwrote any project-specific package a developer had added directly to `apps.json`'s `dependencies`, which `synapps:discover` reads as live state to merge into `composer.json`/`package.json`. Now preserved via `config('synapps.apps.dependencies', [...])`.
- **`synapps:reconfig` — `git_repo` prompt had no default** — leaving it blank (the common case, since nothing showed the existing value) erased the stored repository URL on every run. Prompt now defaults to `config('synapps.apps.git_repo', '')`.
- **Stub theme templates — null user guards** — `auth()->user()->{name,email,avatar_url}` in the bundled theme stubs (`header.blade.php`, `header-nav.blade.php`, `sidebar-menu.blade.php`) now use `auth()->user()?->` null-safe access, matching the guard already applied to the generated app's own theme.

## [2.4.6] - 2026-07-17

### Added
- **`api.log` middleware** (`LogApiRequest`) — opt-in, per-route-group middleware that records every API request/response into a new `api_request_logs` table: method, URL, status code, actor (guest/user/API user), IP, user agent, duration, and the redacted request headers/body and response body. Attach it manually to a route group's middleware array, e.g. `['auth:sanctum', 'api.auth', 'api.token.expiry', 'throttle:api', 'api.log']` — never applied globally. Logging failures are caught and never break the actual API response.
- **`SensitiveDataRedactor` service** — recursively redacts a configurable list of sensitive keys (case-insensitive) from request/response data, replacing values with a self-describing `[REDACTED:{key}]` placeholder instead of a generic mask. `UploadedFile` instances are replaced with `[FILE:{field}:{original_name}]` so multipart uploads don't break JSON serialization. Oversized payloads are safely truncated into a valid-JSON `{ "_truncated": true, "original_size", "preview" }` envelope rather than a raw cut-off string.
- **`ApiRequestLog` model** and **`ApiLogActorType` enum** (`Guest`/`User`/`ApiUser`) backing the new table.
- **`logging` block in generated `apps.json`** — `synapps:setup` and `synapps:reconfig` now scaffold `logging.sensitive_keys` (includes `password`, `token`, `authorization`, `cookie`, `set-cookie`, `x-xsrf-token`, etc.) and `logging.max_body_size`, read by the new middleware via `config('synapps.apps.logging.*')`, with hardcoded fallbacks so it works even without a compiled Synapse config.

## [2.4.5] - 2026-07-09

### Added
- **`synapps:vendor-fonts {theme?}`** — new command that self-hosts a theme's Google Fonts: auto-detects the `fonts.googleapis.com/css2` link in `theme.json`'s `headers.*`, downloads the referenced woff2 files and rewrites the CSS to reference them locally, removes the Google `<link>`/`preconnect` entries, and registers the local stylesheet in `theme.json`'s `css.*`. Atomic — nothing is written to disk until every font file downloads successfully.
- **`csp_nonce()` helper** — returns the per-request CSP nonce bound by `SecureHeaders`, for stamping onto inline `<script>` tags.
- **`ThemeService::renderStyles()`/`renderScripts()` — Subresource Integrity support** — CDN entries in `theme.json`'s `css`/`js` arrays may now be an object (`{ src/href, integrity, crossorigin }`) instead of a plain URL string, rendering the `integrity`/`crossorigin` attributes. Plain string entries still render exactly as before.
- **`csp` block in generated `apps.json`** — `synapps:setup` and `synapps:reconfig` now scaffold an empty `csp.{script_src,style_src,font_src,img_src,connect_src}` block, read by `SecureHeaders` to extend the CSP allowlist per-app without hardcoding any project's CDN hosts into the shared package.

### Fixed
- **`SecureHeaders` — hardened Content-Security-Policy** — `script-src` no longer allows `'unsafe-inline'` (replaced with a per-request nonce); the blanket `https:` wildcard on `script-src`/`style-src`/`img-src`/`font-src`/`connect-src` is replaced by an explicit, config-driven allowlist; added `frame-ancestors 'self'` and `form-action 'self'` (previously undefined, meaning unrestricted).
- **`adv-select` — click-away not closing inside modals** — `@click.away` now runs in the capture phase (`@click.away.capture`), since ancestors like `<x-synapse-modal>`'s content box call `stopPropagation()` on click, which previously prevented the bubble-phase listener from ever firing.

## [2.4.4] - 2026-06-27

### Changed
- **`SynAppsPatchTests` — smarter `.env.testing` handling** — instead of overwriting the file on every run, the command now diffs existing keys against required keys and only appends the missing ones (with a backup). This preserves any custom values the developer has set.
- **`SynAppsPatchTests` — `APP_LOCALE` and `APP_FALLBACK_LOCALE` required keys** — both are now added to `.env.testing` (value: `en`) and to `phpunit.xml` as `force="true"` env vars, ensuring locale-dependent tests are deterministic.
- **`SynAppsPatchTests` — `phpunit.xml` `force` attribute support** — `ensureEnvVar()` now accepts a `$force` flag and writes or removes the `force="true"` attribute accordingly, instead of always omitting it.

### Fixed
- **`ExcelExport` PHPDoc** — `$status` property typed as `ExcelTaskStatus` (was `string`), matching the actual cast.

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
