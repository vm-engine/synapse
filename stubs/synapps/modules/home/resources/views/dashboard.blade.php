<x-synapps::layouts.app>
    <div
        class="p-5"
        x-data="{ pageName: 'Dashboard', isHome: false }"
    >
        <x-synapse-breadcrumbs :breadcrumbs="$breadcrumbs ?? null" />
        <h3 class="text-2xl">Dashboard</h3>
    </div>
</x-synapps::layouts.app>
