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
Version: 1.0.0
--------------------------------------------------------------------
--}}

<div
    class="adv-select-container relative w-full"
    x-data="{
        search: '',
        results: [],
        selected: @js($preload ?? []),
        multiple: {{ $multiple ? 'true' : 'false' }},
        open: false,
        searchMethod: {{ $searchMethod ? "'{$searchMethod}'" : 'null' }},
        loadMethod: {{ $loadMethod ? "'{$loadMethod}'" : 'null' }},
        wireModel: '{{ $wireModel }}',
        labelField: '{{ $labelField }}',
        valueField: '{{ $valueField }}',
        staticOptions: @js($options),
        minSearchLength: {{ $minSearchLength }},
        isLive: {{ $live ? 'true' : 'false' }},
        allowCreate: {{ $allowCreate ? 'true' : 'false' }},
        autoLoad: {{ $autoLoad ? 'true' : 'false' }},
    
        async init() {
            // Auto-load selected values from wireModel if enabled
            if (this.autoLoad && this.selected.length === 0) {
                await this.loadInitialValues();
            }

            // Set initial value to Livewire model
            // Only sync if preload has values (avoid empty updates on first render)
            if (this.selected && this.selected.length > 0) {
                this.syncWithWire();
            }

            // Reopen dropdown after Livewire updates
            Livewire.hook('message.processed', () => {
                if (document.activeElement === this.$refs.search && this.search
                    .length >
                    0) {
                    this.open = true;
                }
            });

            // Listen for reset event
            window.addEventListener('reset-tag-input', () => {
                this.clearSelection();
            });
        },

        async loadInitialValues() {
            if (!this.$wire) return;

            try {
                // Get current value from Livewire model
                const currentValue = this.$wire.get(this.wireModel);

                if (currentValue === null || currentValue === undefined || (Array.isArray(currentValue) && currentValue.length === 0)) {
                    return; // No value to load
                }

                const values = Array.isArray(currentValue) ? currentValue : [currentValue];

                // Try to find values in static options first
                const foundInStatic = [];
                for (const val of values) {
                    // Use loose equality to handle type mismatches (string vs number)
                    const found = this.staticOptions.find(o => o[this.valueField] == val);
                    if (found) {
                        foundInStatic.push(found);
                    }
                }

                // If all values found in static options, use them
                if (foundInStatic.length === values.length) {
                    this.selected = foundInStatic;
                    return;
                }

                // If loadMethod is provided, call it to get full objects
                if (this.loadMethod && this.$wire?.call) {
                    const loaded = await this.$wire.call(this.loadMethod, this.multiple ? values : values[0]);

                    if (loaded) {
                        this.selected = Array.isArray(loaded) ? loaded : [loaded];
                        return;
                    }
                }

                // Fallback: create simple objects with value = label
                this.selected = values.map(val => ({
                    [this.valueField]: val,
                    [this.labelField]: val
                }));
            } catch (e) {
                console.error('Error loading initial values:', e);
            }
        },
    
        focusInput() {
            this.$refs.search.classList.remove('hidden');
            this.$refs.search.focus();
            this.open = true;
        },
    
        async fetch() {
            const query = this.search.toLowerCase();
    
            // ✅ 1. Start with static options (filter client-side)
            let staticResults = this.staticOptions.filter(o =>
                o[this.labelField].toLowerCase().includes(query)
            );
    
            // ✅ 2. If a Livewire search method is provided, merge remote results
            if (this.searchMethod && query.length > this.minSearchLength && this.$wire?.call) {
                try {
                    const res = await this.$wire.call(this.searchMethod, this.search);
                    const remoteResults = Array.isArray(res) ? res : [];
                    // merge while avoiding duplicates by valueField
                    const merged = [...staticResults];
                    for (const r of remoteResults) {
                        if (!merged.find(o => o[this.valueField] == r[this.valueField])) {
                            merged.push(r);
                        }
                    }
                    this.results = merged;
                } catch (e) {
                    console.error('search error', e);
                    this.results = staticResults;
                }
            } else {
                // no remote search — static only
                this.results = staticResults;
            }
    
            this.open = this.results.length > 0;
        },
    
        select(item) {
            if (this.multiple) {
                if (!this.selected.find(i => i[this.valueField] == item[this
                        .valueField])) {
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
    
            // Add to static options for future searches
            this.staticOptions.push(newItem);
    
            // Select the new item
            this.select(newItem);
        },
    
        handleKeydown(e) {
            if (e.key === 'Enter' && this.allowCreate && this.search.trim() && this.results.length === 0) {
                e.preventDefault();
                this.createNew();
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
            if (!this.$wire) return; // safety check

            const ids = this.selected.map(i => i[this.valueField]);

            // Prevent sending [] on init unless already synced once
            if (!this._initialized) {
                this._initialized = true;
                if (ids.length === 0) return; // skip initial empty sync
            }

            this.$wire.set(this.wireModel, this.multiple ? ids : (ids.length > 0 ? ids[0] : null), this.live);
        },
    }"
    x-init="init()"
    wire:ignore
>
    {{-- Select box --}}
    <div
        class="adv-select-input-box shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 focus:ring-3 focus:outline-hidden flex w-full flex-wrap gap-1 rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
        @click="focusInput"
        @click.away="open = false"
    >
        {{-- Selected items (multiple mode) --}}
        <template x-if="multiple">
            <template
                x-for="(item, index) in selected"
                :key="item[valueField]"
            >
                <span
                    class="adv-select-item-multiple flex items-center space-x-1 rounded-md bg-blue-100 px-2 py-1 text-blue-700"
                >
                    <span x-text="item[labelField]"></span>
                    <button
                        class="ml-1 text-sm"
                        type="button"
                        @click.stop="remove(index)"
                    >
                        ✕
                    </button>
                </span>
            </template>
        </template>

        {{-- Single select display --}}
        <template x-if="!multiple && selected.length">
            <span
                class="adv-select-item-single text-gray-800"
                x-text="selected[0][labelField]"
            ></span>
        </template>

        {{-- Input --}}
        <input
            class="adv-select-input flex-1 focus:outline-none"
            type="text"
            x-ref="search"
            x-model="search"
            x-on:focus="fetch()"
            x-on:input.debounce.300ms="fetch()"
            x-on:keydown="handleKeydown($event)"
            placeholder="{{ $placeholder }}"
            :class="{ 'hidden': !multiple && selected.length }"
        />

        {{-- Clear button for single select --}}
        <template x-if="!multiple && selected.length">
            <button
                class="adv-select-clear ml-auto text-sm text-gray-400 hover:text-gray-700"
                type="button"
                @click.stop="clearSelection"
            >
                ✕
            </button>
        </template>
    </div>

    {{-- Dropdown --}}
    <div
        class="adv-select-dropdown absolute z-50 mt-1 max-h-60 w-full overflow-y-auto rounded-md border bg-white shadow-md dark:border-gray-700 dark:bg-gray-900"
        x-show="open && (results.length > 0 || (allowCreate && search.trim()))"
        x-transition
    >
        <template
            x-for="item in results"
            :key="item[valueField]"
        >
            <div
                class="adv-select-option cursor-pointer p-2 hover:bg-blue-100 dark:hover:bg-gray-800"
                x-text="item[labelField]"
                @click="select(item)"
            ></div>
        </template>

        {{-- Create new option hint --}}
        <template x-if="allowCreate && search.trim() && results.length === 0">
            <div
                class="adv-select-option-create cursor-pointer border-t border-gray-200 p-2 hover:bg-green-100 dark:border-gray-700 dark:hover:bg-green-900/20"
                @click="createNew()"
            >
                <span class="text-green-600 dark:text-green-400">
                    <i class="fa-solid fa-plus mr-1"></i>
                    Create "<span x-text="search"></span>"
                </span>
            </div>
        </template>
    </div>
</div>
