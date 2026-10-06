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

