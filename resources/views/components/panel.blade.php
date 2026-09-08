{{--
    Panel Component

    A card-shell wrapper for list/form pages, with a header (title + optional
    toolbar) and a body slot. Replaces the hand-rolled
    "rounded-2xl border ... dark:bg-white/3" card markup duplicated across
    list/form views.

    Props:
    - title (string): Optional panel header title
    - collapsible (bool): Make the body collapsible via the header (default: false)
    - collapsed (bool): Start collapsed — only applies when collapsible (default: false)

    Slots:
    - default: Panel body (table, form, etc.) — unstyled, caller keeps its own padding
    - toolbar: Optional header-right actions (add button, search, filter toggle) —
      always visible, even when the body is collapsed

    Usage Examples:

    <x-synapse-panel :title="__('pm::client.list_title')">
        <x-slot:toolbar>
            <a class="btn primary" href="{{ backend_route('pm.client.create') }}">Add</a>
        </x-slot:toolbar>

        <table>...</table>
    </x-synapse-panel>

    <x-synapse-panel title="Filters" collapsible collapsed>
        <table>...</table>
    </x-synapse-panel>
--}}

<div
    class="syn-panel"
    @if ($collapsible) x-data="{ open: {{ $collapsed ? 'false' : 'true' }} }" @endif
    {{ $attributes }}
>
    @if ($title || (isset($toolbar) && $toolbar->isNotEmpty()))
        <div
            class="syn-panel-header"
            @if ($collapsible) @click="open = !open" @endif
        >
            @if ($title)
                <div class="syn-panel-title-wrap">
                    <h3 class="syn-panel-title">{{ $title }}</h3>
                    @if ($collapsible)
                        <span
                            class="fa-solid fa-chevron-down syn-panel-chevron"
                            :class="{ 'syn-panel-chevron-open': open }"
                        ></span>
                    @endif
                </div>
            @endif

            @isset($toolbar)
                @if ($toolbar->isNotEmpty())
                    <div class="syn-panel-toolbar">{{ $toolbar }}</div>
                @endif
            @endisset
        </div>
    @endif

    @if ($collapsible)
        <div
            x-show="open"
            x-collapse
        >{{ $slot }}</div>
    @else
        {{ $slot }}
    @endif
</div>
