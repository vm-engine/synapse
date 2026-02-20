<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-white/90">
                <i class="fa-solid fa-list-check text-brand-500 mr-2"></i>
                {{ __('todos::todos.page_title') }}
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ __('todos::todos.page_description') }}
            </p>
        </div>
    </div>

    {{-- Todo Form --}}
    <livewire:todos::todo-form />

    {{-- Todo List --}}
    <livewire:todos::todo-list />

    {{-- Toastr Notifications --}}
    <x-synapse-toastr />
</div>
