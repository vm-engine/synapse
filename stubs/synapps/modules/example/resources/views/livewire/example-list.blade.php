<div x-data="{ pageName: 'Example List', isHome: false, drawerOpen: false }">
    @include('synapps::components.layouts.partials.breadcrumbs')

    <!-- Confirmation Dialog -->
    <x-synapse-confirm-dialog />

    <!-- DataTales Example -->
    <div class="space-y-5 sm:space-y-6">

        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">

            <div class="flex justify-between">
                <div class="px-5 py-4 sm:px-6 sm:py-5">
                    <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                        Example List
                    </h3>
                </div>
                <div class="my-auto px-5 flex gap-3">
                    <div>
                        <a
                            class="btn primary"
                            href="{{ route('backend.example.form') }}"
                            wire:navigate
                        >
                            <span class="fa-solid fa-plus"></span>
                            Add
                        </a>
                    </div>
                    <div class="input-group">
                        <button
                            class="input-group-item right btn"
                            type="button"
                            title="Advanced Filter"
                            @click="drawerOpen = true"
                        ><span class="fa-solid fa-filter"></span></button>
                        <input
                            class="p-2 form-input"
                            id="q"
                            name="q"
                            type=" text"
                            placeholder="Search"
                            wire:model.live.debounce="q"
                        >
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-100 dark:border-gray-800">
                <div class="max-w-full overflow-x-auto">
                    <table
                        class="min-w-full datatable"
                        id="dataTable"
                        width="100%"
                        cellspacing="0"
                    >
                        <thead>
                            <tr>
                                <th>
                                    <p>No.</p>
                                </th>
                                <th>
                                    <p>Text</p>
                                </th>
                                <th>
                                    <p>Category</p>
                                </th>
                                <th>
                                    <p>Email</p>
                                </th>
                                <th>
                                    <p>Number</p>
                                </th>
                                <th>
                                    <p>Textarea</p>
                                </th>
                                <th class="sortable">
                                    <div>
                                        <p>Options</p>
                                        <a
                                            href="#"
                                            wire:click.prevent="sortData('dropdown')"
                                        >
                                            <span
                                                class="fa fa-solid {{ $sort != 'dropdown' ? 'fa-sort' : ($sortDirection == 'asc' ? 'fa-sort-up' : 'fa-sort-down') }}"
                                            ></span>
                                        </a>
                                    </div>
                                </th>
                                <th class="sortable">
                                    <div>
                                        <p>Date Time</p>
                                        <a
                                            href="#"
                                            wire:click.prevent="sortData('datetime')"
                                        >
                                            <span
                                                class="fa fa-solid {{ $sort != 'datetime' ? 'fa-sort' : ($sortDirection == 'asc' ? 'fa-sort-up' : 'fa-sort-down') }}"
                                            ></span>
                                        </a>
                                    </div>
                                </th>
                                <th>
                                    <p>Actions</p>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $i = 1;
                            @endphp
                            @foreach ($this->exampleList as $list)
                                <tr>
                                    <td>
                                        <div class="flex items-center">
                                            <p>{{ ($this->exampleList->currentPage() - 1) * $limit + $i }}</p>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex items-center">
                                            <p>{{ $list->text }}</p>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex items-center">
                                            @if($list->category)
                                                <button
                                                    type="button"
                                                    wire:click="filterByCategory({{ $list->category_id }})"
                                                    class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-sm font-medium
                                                           transition-colors cursor-pointer
                                                           {{ in_array($list->category_id, $filterCategories)
                                                               ? 'bg-brand-100 text-brand-700 dark:bg-brand-900/30 dark:text-brand-300'
                                                               : 'bg-gray-100 text-gray-700 hover:bg-brand-50 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-brand-900/20' }}"
                                                    title="Click to filter by this category"
                                                >
                                                    <span class="fa-solid fa-folder text-xs"></span>
                                                    <span>{{ $list->category->name }}</span>
                                                </button>
                                            @else
                                                <span class="text-gray-400 dark:text-gray-600 text-sm italic">No category</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex items-center">
                                            <p class="">{{ $list->email }}</p>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex items-center">
                                            <p>{{ $list->number }}
                                            </p>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex items-center">
                                            <p>{{ $list->textarea }}
                                            </p>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex items-center">
                                            <p>{{ $list->dropdown + 1 }}
                                            </p>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex items-center">
                                            <p>{{ $list->datetime }}
                                            </p>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-center">
                                            <a
                                                class="btn-icon warning has-tooltip group"
                                                href="{{ route('backend.example.form', ['id' => $list->id]) }}"
                                                wire:navigate
                                            >
                                                <span class="fa-solid fa-edit"></span>
                                                <span class="tooltip">Edit</span>
                                            </a>
                                            <button
                                                class="btn-icon danger has-tooltip group"
                                                type="button"
                                                wire:click="$dispatch('confirm-dialog', {
                                                    title: 'Delete Example',
                                                    message: 'Are you sure you want to delete this &quot;{{ addslashes($list->text) }}&quot; data? This action cannot be undone.',
                                                    confirmText: 'Yes, Delete',
                                                    cancelText: 'Cancel',
                                                    confirmColor: 'danger',
                                                    icon: 'fa-solid fa-trash',
                                                    wireMethod: 'delete',
                                                    wireParams: [{{ $list->id }}],
                                                    wireComponent: '{{ $this->getId() }}'
                                                })"
                                            >
                                                <span class="fa-solid fa-trash"></span>
                                                <span class="tooltip">Delete</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @php
                                    $i++;
                                @endphp
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="p-3 flex gap-2">
                <div class="flex-none p-1">
                    <select
                        class="shadow-theme-sm rounded-sm p-1"
                        id="limit"
                        name="limit"
                        wire:model.live="limit"
                    >
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
                <div class="flex-1 grow pagination-container">
                    {{ $this->exampleList->links() }}
                </div>
            </div>
        </div>
    </div>

    @php
        $advanceTitle = '<span class="fa-solid fa-filter mr-2"></span>Advanced Filter';
    @endphp
    <x-synapse-drawer :title="$advanceTitle">
        <div class="space-y-6">
            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Dropdown Option
                </label>
                <x-synapse-select
                    id="filterOption"
                    name="filterOption"
                    wire:model.live="filterOption"
                    :options="$options"
                >
                </x-synapse-select>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Categories
                </label>
                <x-synapse-select2
                    name="filterCategories"
                    :options="$categories"
                    :model="$filterCategories"
                    :multiple="true"
                    wire:model.live="filterCategories"
                    placeholder="Select categories"
                />
            </div>
        </div>
    </x-synapse-drawer>
</div>
