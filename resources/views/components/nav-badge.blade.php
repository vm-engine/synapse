{{--
    Nav Badge Component

    The count beside a backend menu item. The item names its badge class in module.json ("badge");
    see README → Menu badges. Renders nothing at all without a count (an empty element would still
    take a flex gap in the menu row); hidden with the item text when the sidebar is collapsed.

    Usage (already in the nav stubs): <x-synapse-nav-badge :item="$item" />
--}}

@if ($label() !== '')
    <span
        class="syn-nav-badge"
        :class="sidebarToggle ? 'lg:hidden' : ''"
    >
        <x-synapse-badge color="gray" size="sm">{{ $label() }}</x-synapse-badge>
    </span>
@endif
