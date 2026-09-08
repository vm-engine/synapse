{{--
    Search Box Component

    A text input with a search icon fixed to the left, and an optional
    trailing slot for an appended action (e.g. a filter-drawer toggle button).

    Props:
    - placeholder (string): Optional input placeholder (no hardcoded default — pass a translated string)

    Slots:
    - trailing: Optional element appended after the input (e.g. a filter button)

    All other attributes (wire:model, id, name, ...) are passed through to the <input>.

    Usage Examples:

    <x-synapse-search-box
        :placeholder="__('pm::client.search_placeholder')"
        wire:model.live.debounce="q"
    />

    <x-synapse-search-box placeholder="Search..." wire:model.live.debounce="q">
        <x-slot:trailing>
            <button type="button" class="btn" @click="drawerOpen = true">
                <span class="ph ph-funnel"></span>
            </button>
        </x-slot:trailing>
    </x-synapse-search-box>
--}}

<div class="syn-search-box">
    <span class="syn-search-box-icon">
        <i class="ph ph-magnifying-glass"></i>
    </span>

    <input
        type="text"
        placeholder="{{ $placeholder }}"
        {{ $attributes->merge(['class' => 'syn-search-box-input ' . (isset($trailing) ? 'syn-search-box-has-trailing' : '')]) }}
    />

    @isset($trailing)
        <div class="syn-search-box-trailing">{{ $trailing }}</div>
    @endisset
</div>
