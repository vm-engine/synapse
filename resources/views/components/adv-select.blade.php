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
| multiple          | bool        | false               | Enables multiple selection mode. |
| placeholder       | string      | 'Select an option'  | Placeholder text shown in the input. |
| labelField        | string      | 'label'             | Field name used for option labels. |
| valueField        | string      | 'value'             | Field name used for option values. |
| preload           | array       | []                  | Preselected options (for editing existing data). |
| options           | array       | []                  | Static options for client-side dropdowns. |
| minSearchLength   | int         | 2                   | Minimum characters before remote search triggers. |
| live              | bool        | false               | If true, updates Livewire immediately on change. |

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

Combined static + remote
<x-adv-select
    wire-model="selectedUser"
    search-method="searchUsers"
    :options="[
        ['value' => 1, 'label' => 'System Admin'],
        ['value' => 2, 'label' => 'Guest User'],
    ]"
/>

--------------------------------------------------------------------
📘 Livewire search method example:
--------------------------------------------------------------------
public function searchUsers(string $query): array
{
    return User::where('name', 'like', "%{$query}%")
        ->limit(10)
        ->get(['id as value', 'name as label'])
        ->toArray();
}
--------------------------------------------------------------------

Component: VmEngine\Synapse\Components\AdvSelect
View: synapse::components.adv-select
Version: 1.0.0
--------------------------------------------------------------------
--}}

<div
    class="relative w-full"
    x-data="{
        search: '',
        results: [],
        selected: {{ $preload ?? '[]' }},
        multiple: {{ $multiple ? 'true' : 'false' }},
        open: false,
        searchMethod: {{ $searchMethod ? "'{$searchMethod}'" : 'null' }},
        wireModel: '{{ $wireModel }}',
        labelField: '{{ $labelField }}',
        valueField: '{{ $valueField }}',
        staticOptions: @js($options),
        minSearchLength: {{ $minSearchLength }},
        isLive: {{ $live ? 'true' : 'false' }},
    
        init() {
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
                        if (!merged.find(o => o[this.valueField] === r[this.valueField])) {
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
                if (!this.selected.find(i => i[this.valueField] === item[this
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
    
            this.$wire.set(this.wireModel, this.multiple ? ids : (ids[0] || null), this.live);
        },
    }"
    x-init="init()"
    wire:ignore
>
    {{-- Select box --}}
    <div
        class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 flex flex-wrap gap-1"
        @click="focusInput"
        @click.away="open = false"
    >
        {{-- Selected items (multiple mode) --}}
        <template x-if="multiple">
            <template
                x-for="(item, index) in selected"
                :key="item[valueField]"
            >
                <span class="bg-blue-100 text-blue-700 px-2 py-1 rounded-md flex items-center space-x-1">
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
                class="text-gray-800"
                x-text="selected[0][labelField]"
            ></span>
        </template>

        {{-- Input --}}
        <input
            class="flex-1 focus:outline-none"
            type="text"
            x-ref="search"
            x-model="search"
            x-on:focus="fetch()"
            x-on:input.debounce.300ms="fetch()"
            placeholder="{{ $placeholder }}"
            :class="{ 'hidden': !multiple && selected.length }"
        />

        {{-- Clear button for single select --}}
        <template x-if="!multiple && selected.length">
            <button
                class="text-gray-400 hover:text-gray-700 ml-auto text-sm"
                type="button"
                @click.stop="clearSelection"
            >
                ✕
            </button>
        </template>
    </div>

    {{-- Dropdown --}}
    <div
        class="absolute z-50 mt-1 w-full bg-white border rounded-md shadow-md max-h-60 overflow-y-auto"
        x-show="open && results.length > 0"
        x-transition
    >
        <template
            x-for="item in results"
            :key="item[valueField]"
        >
            <div
                class="p-2 hover:bg-blue-100 cursor-pointer"
                x-text="item[labelField]"
                @click="select(item)"
            ></div>
        </template>
    </div>
</div>
