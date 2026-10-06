{{--
    Companion to <livewire:synotif::bell />. Kept as a separate, non-Livewire
    embed (rather than nested inside bell.blade.php) because Livewire v4
    can't nest a namespaced child component (`synotif::list`) inside another
    Livewire component's own template — the compiler tries to use the
    component name as an HTML tag and rejects the `::`. Embed both this and
    the bell in the layout, next to each other.
--}}
<x-synapse::modal
    name="notifications"
    maxWidth="lg"
>
    @livewire('synotif::list')
</x-synapse::modal>
