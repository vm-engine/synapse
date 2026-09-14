{{--
====================================================================
  <x-adv-select> — Advanced Select Component (Livewire + Alpine.js)
====================================================================

A fully featured, reactive select component that supports:
- ✅ Single or multiple selection
- ✅ Searchable input (local + remote data)
- ✅ Static options
- ✅ Preloaded selections
- ✅ Full Livewire synchronization
- ✅ Works with Livewire SPA navigation
- ✅ Type-safe (no empty syncs on initialization)

--------------------------------------------------------------------
🔧 Props
--------------------------------------------------------------------
| Name              | Type        | Default             | Description |
|-------------------|-------------|---------------------|--------------|
| wireModel         | string      | —                   | The Livewire property to bind. |
| searchMethod      | string|null | null                | Optional Livewire method name for remote search. |
| loadMethod        | string|null | null                | Optional Livewire method to load initial values by ID(s). |
| multiple          | bool        | false               | Enables multiple selection mode. |
| placeholder       | string      | 'Select an option'  | Placeholder text shown in the input. |
| labelField        | string      | 'label'             | Field name used for option labels. |
| valueField        | string      | 'value'             | Field name used for option values. |
| preload           | array       | []                  | Preselected options (for editing existing data). |
| options           | array       | []                  | Static options for client-side dropdowns. |
| minSearchLength   | int         | 2                   | Minimum characters before remote search triggers. |
| live              | bool        | false               | If true, updates Livewire immediately on change. |
| allowCreate       | bool        | false               | If true, allows user to create new options by pressing Enter. |
| autoLoad          | bool        | true                | If true, auto-loads selected values from wireModel on init. |
| disabled          | bool        | false               | If true, the select is non-interactive and visually dimmed. |

--------------------------------------------------------------------
💡 Examples
--------------------------------------------------------------------

Static single select
<x-adv-select
    wire-model="role"
    :options="[
        ['value' => 'admin', 'label' => 'Administrator'],
        ['value' => 'editor', 'label' => 'Editor'],
        ['value' => 'user', 'label' => 'User'],
    ]"
/>

Multiple select
<x-adv-select
    wire-model="selectedTags"
    :multiple="true"
    :options="[
        ['value' => 'laravel', 'label' => 'Laravel'],
        ['value' => 'alpine', 'label' => 'Alpine.js'],
        ['value' => 'livewire', 'label' => 'Livewire'],
    ]"
/>

Remote search (Livewire method)
<x-adv-select
    wire-model="selectedUser"
    search-method="searchUsers"
    placeholder="Search users..."
/>

Auto-load with AJAX (edit mode)
<x-adv-select
    wire-model="userId"
    search-method="searchUsers"
    load-method="loadUser"
    placeholder="Select user..."
/>

Combined static + remote
<x-adv-select
    wire-model="selectedUser"
    search-method="searchUsers"
    :options="[
        ['value' => 1, 'label' => 'System Admin'],
        ['value' => 2, 'label' => 'Guest User'],
    ]"
/>

Auto-load tags (multiple)
<x-adv-select
    wire-model="tags"
    :multiple="true"
    :allow-create="true"
/>

--------------------------------------------------------------------
📘 Livewire methods example:
--------------------------------------------------------------------
// Search method (for dropdown search)
public function searchUsers(string $query): array
{
    return User::where('name', 'like', "%{$query}%")
        ->limit(10)
        ->get(['id as value', 'name as label'])
        ->toArray();
}

// Load method (for auto-loading initial values by ID)
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
--------------------------------------------------------------------

Component: VmEngine\Synapse\Components\AdvSelect
View: synapse::components.adv-select
Version: 1.2.0 — added a `disabled` prop (native disabled on the search
input and clear/remove buttons, `is-disabled` CSS class, and
focusInput() is a no-op while disabled).
--------------------------------------------------------------------
--}}

<div
    class="adv-select-container"
    {{-- Options/preload are inlined as raw JSON array/object literals (not
         Blade's @js(), which wraps them in JSON.parse(...)) because Livewire's
         CSP-safe Alpine evaluator (see SynapseServiceProvider::overrideLivewireCsp())
         has no access to the `JSON` global — JSON.parse() inside an x-data
         expression throws "Undefined variable: JSON" and silently falls back
         to an empty data object, breaking every binding in this component. --}}
    x-data="synapseAdvSelect({{ json_encode($preload ?? []) }}, {{ $multiple ? 'true' : 'false' }}, {{ $searchMethod ? "'{$searchMethod}'" : 'null' }}, {{ $loadMethod ? "'{$loadMethod}'" : 'null' }}, '{{ $wireModel }}', '{{ $labelField }}', '{{ $valueField }}', {{ json_encode($options) }}, {{ $minSearchLength }}, {{ $live ? 'true' : 'false' }}, {{ $allowCreate ? 'true' : 'false' }}, {{ $autoLoad ? 'true' : 'false' }}, {{ $disabled ? 'true' : 'false' }})"
    x-init="init()"
    wire:ignore
    {{-- .capture: some ancestors (e.g. <x-synapse-modal>'s content box) call
         stopPropagation() on click, which would otherwise prevent this
         bubble-phase document listener from ever firing inside a modal.
         Capture-phase runs before that stopPropagation() takes effect.
         This lives on the container (not just the input box) because the
         dropdown's search input is now a container descendant, not a box
         descendant — clicking into it must not count as "away". --}}
    @click.away.capture="open = false"
>
    {{-- Select box (display-only: placeholder / selection / pills + clear + arrow) --}}
    <div
        class="adv-select-input-box"
        :class="{ 'is-open': open, 'is-disabled': disabled }"
        :aria-disabled="disabled"
        @click="toggleOpen"
    >
        {{-- Placeholder (nothing selected, either mode) --}}
        <template x-if="selected.length === 0">
            <span class="adv-select-placeholder">{{ $placeholder }}</span>
        </template>

        {{-- Selected items (multiple mode) --}}
        <template x-if="multiple">
            <template
                x-for="(item, index) in selected"
                :key="item[valueField]"
            >
                <span class="adv-select-item-multiple">
                    <span x-text="item[labelField]"></span>
                    <button
                        class="adv-select-item-remove"
                        type="button"
                        :disabled="disabled"
                        @click.stop="remove(index)"
                    >
                        <i class="ph ph-x"></i>
                    </button>
                </span>
            </template>
        </template>

        {{-- Single select display --}}
        <template x-if="!multiple && selected.length">
            <span
                class="adv-select-item-single"
                x-text="selected[0][labelField]"
            ></span>
        </template>

        {{-- Clear button for single select --}}
        <template x-if="!multiple && selected.length">
            <button
                class="adv-select-clear"
                type="button"
                :disabled="disabled"
                @click.stop="clearSelection"
            >
                <i class="ph ph-x"></i>
            </button>
        </template>

        {{-- Dropdown arrow --}}
        <i
            class="ph ph-caret-down adv-select-arrow"
            :class="{ 'is-open': open }"
        ></i>
    </div>

    {{-- Dropdown --}}
    <div
        class="adv-select-dropdown"
        x-show="open"
        x-transition
    >
        {{-- Search input --}}
        <input
            class="adv-select-input"
            type="text"
            x-ref="search"
            x-model="search"
            :disabled="disabled"
            x-on:focus="fetch()"
            x-on:input.debounce.300ms="fetch()"
            x-on:keydown="handleKeydown($event)"
            placeholder="{{ $placeholder }}"
        />

        <template
            x-for="item in results"
            :key="item[valueField]"
        >
            <div
                class="adv-select-option"
                x-text="item[labelField]"
                @click="select(item)"
            ></div>
        </template>

        {{-- Create new option hint --}}
        <template x-if="allowCreate && search.trim() && results.length === 0">
            <div
                class="adv-select-option-create"
                @click="createNew()"
            >
                <span class="adv-select-create-hint">
                    <i class="ph ph-plus mr-1"></i>
                    Create "<span x-text="search"></span>"
                </span>
            </div>
        </template>
    </div>
</div>

<script nonce="{{ csp_nonce() }}">
    (() => {
        const register = () => {
            Alpine.data('synapseAdvSelect', (preload, multiple, searchMethod, loadMethod, wireModel, labelField,
                valueField,
                staticOptions, minSearchLength, isLive, allowCreate, autoLoad, disabled) => ({
                search: '',
                results: [],
                selected: preload,
                multiple,
                open: false,
                searchMethod,
                loadMethod,
                wireModel,
                labelField,
                valueField,
                staticOptions,
                minSearchLength,
                isLive,
                allowCreate,
                autoLoad,
                disabled,

                async init() {
                    if (this.autoLoad && this.selected.length === 0) {
                        await this.loadInitialValues();
                    }

                    if (this.selected && this.selected.length > 0) {
                        this.syncWithWire();
                    }

                    Livewire.interceptMessage(({
                        onSuccess
                    }) => {
                        onSuccess(() => {
                            if (document.activeElement === this.$refs.search &&
                                this.search
                                .length > 0) {
                                this.open = true;
                            }
                        });
                    });

                    window.addEventListener('reset-tag-input', () => {
                        this.clearSelection();
                    });
                },

                async loadInitialValues() {
                    if (!this.$wire) return;

                    try {
                        const currentValue = this.$wire.get(this.wireModel);

                        if (currentValue === null || currentValue === undefined || (Array
                                .isArray(
                                    currentValue) && currentValue.length === 0)) {
                            return;
                        }

                        const values = Array.isArray(currentValue) ? currentValue : [
                            currentValue
                        ];

                        const foundInStatic = [];
                        for (const val of values) {
                            const found = this.staticOptions.find(o => o[this.valueField] ==
                                val);
                            if (found) {
                                foundInStatic.push(found);
                            }
                        }

                        if (foundInStatic.length === values.length) {
                            this.selected = foundInStatic;
                            return;
                        }

                        if (this.loadMethod && this.$wire?.call) {
                            const loaded = await this.$wire.call(this.loadMethod, this
                                .multiple ? values :
                                values[0]);

                            if (loaded) {
                                this.selected = Array.isArray(loaded) ? loaded : [loaded];
                                return;
                            }
                        }

                        this.selected = values.map(val => ({
                            [this.valueField]: val,
                            [this.labelField]: val
                        }));
                    } catch (e) {
                        console.error('Error loading initial values:', e);
                    }
                },

                focusInput() {
                    if (this.disabled) return;
                    this.open = true;
                    this.$nextTick(() => this.$refs.search?.focus());
                },

                toggleOpen() {
                    if (this.disabled) return;
                    if (this.open) {
                        this.open = false;
                        this.$refs.search?.blur();
                    } else {
                        this.focusInput();
                    }
                },

                async fetch() {
                    const query = this.search.toLowerCase();

                    let staticResults = this.staticOptions.filter(o =>
                        o[this.labelField].toLowerCase().includes(query)
                    );

                    if (this.searchMethod && query.length > this.minSearchLength && this.$wire
                        ?.call) {
                        try {
                            const res = await this.$wire.call(this.searchMethod, this.search);
                            const remoteResults = Array.isArray(res) ? res : [];
                            const merged = [...staticResults];
                            for (const r of remoteResults) {
                                if (!merged.find(o => o[this.valueField] == r[this
                                        .valueField])) {
                                    merged.push(r);
                                }
                            }
                            this.results = merged;
                        } catch (e) {
                            console.error('search error', e);
                            this.results = staticResults;
                        }
                    } else {
                        this.results = staticResults;
                    }
                },

                select(item) {
                    if (this.multiple) {
                        if (!this.selected.find(i => i[this.valueField] == item[this.valueField])) {
                            this.selected.push(item);
                        }
                    } else {
                        this.selected = [item];
                        this.$refs.search.blur();
                    }
                    this.search = '';
                    this.results = [];
                    this.open = false;
                    this.syncWithWire();
                },

                createNew() {
                    if (!this.allowCreate || !this.search.trim()) return;

                    const newItem = {
                        [this.valueField]: this.search.trim(),
                        [this.labelField]: this.search.trim()
                    };

                    this.staticOptions.push(newItem);
                    this.select(newItem);
                },

                handleKeydown(e) {
                    if (e.key === 'Enter' && this.allowCreate && this.search.trim() && this.results
                        .length === 0) {
                        e.preventDefault();
                        this.createNew();
                    }
                    if (e.key === 'Escape') {
                        this.open = false;
                        this.$refs.search.blur();
                    }
                },

                remove(index) {
                    this.selected.splice(index, 1);
                    this.syncWithWire();
                },

                clearSelection() {
                    this.selected = [];
                    this.syncWithWire();
                    this.focusInput();
                },

                syncWithWire() {
                    if (!this.$wire) return;

                    const ids = this.selected.map(i => i[this.valueField]);

                    if (!this._initialized) {
                        this._initialized = true;
                        if (ids.length === 0) return;
                    }

                    this.$wire.set(this.wireModel, this.multiple ? ids : (ids.length > 0 ? ids[0] :
                            null), this
                        .isLive);
                },
            }));
        };
        document.addEventListener('alpine:init', register);
        if (window.Alpine) {
            register();
        }
    })();
</script>
