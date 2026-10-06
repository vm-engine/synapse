{{--
    Split Pane Component

    Two-column layout: an aside (tree, list, filters) and a main area. Stacks
    below the lg breakpoint, where the aside hides behind a toggle button.

    Props:
    - asideWidth (string): Initial aside width, number + px|rem|% (default: 20rem)
    - resizable (bool): Show a drag handle between the panes (default: false)
    - storageKey (string): Remember the dragged width per browser (only when resizable)
    - toggleLabel (string): Mobile toggle button label (default: 'Toggle panel')

    Slots:
    - aside: Left pane
    - default: Main pane

    Usage:
    <x-synapse-split-pane aside-width="20rem" resizable storage-key="example-nodes">
        <x-slot:aside>...tree...</x-slot:aside>
        ...editor...
    </x-synapse-split-pane>
--}}

<div
    @class(['syn-split-pane', 'syn-split-pane-resizable' => $resizable])
    style="--syn-split-aside: {{ $asideWidth }}"
    x-data="synapseSplitPane({{ json_encode($resizable ? $storageKey : null) }}, {{ json_encode($asideWidth) }})"
    :style="gridStyle()"
    {{ $attributes }}
>
    <button
        class="btn secondary syn-split-pane-toggle"
        type="button"
        :aria-expanded="asideOpen ? 'true' : 'false'"
        @click="toggleAside()"
    >
        <i class="ph ph-sidebar-simple"></i>
        {{ $toggleLabel }}
    </button>

    <aside
        class="syn-split-pane-aside"
        :class="{ 'syn-split-pane-aside-open': asideOpen }"
    >{{ $aside ?? '' }}</aside>

    @if ($resizable)
        <div
            class="syn-split-pane-handle"
            role="separator"
            aria-orientation="vertical"
            @pointerdown.prevent="startDrag"
        ></div>
    @endif

    <div class="syn-split-pane-main">{{ $slot }}</div>
</div>
