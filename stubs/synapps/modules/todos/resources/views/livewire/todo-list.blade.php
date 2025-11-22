<div class="space-y-4">
    {{-- Confirmation Dialog --}}
    <x-synapse-confirm-dialog />

    {{-- Filters and Search --}}
    <div class="card">
        <div class="card-body">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                {{-- Search --}}
                <div>
                    <label
                        class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400"
                        for="search"
                    >{{ __('todos::todos.filters.search') }}</label>
                    <input
                        class="form-input"
                        id="search"
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('todos::todos.filters.search_placeholder') }}"
                    />
                </div>

                {{-- Sort By --}}
                <div>
                    <label
                        class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400"
                        for="sortBy"
                    >{{ __('todos::todos.filters.sort_by') }}</label>
                    <x-synapse-select
                        id="sortBy"
                        name="sortBy"
                        wire:model.live="sortBy"
                        :options="[
                            'created_desc' => __('todos::todos.sort.newest_first'),
                            'created_asc' => __('todos::todos.sort.oldest_first'),
                            'due_date' => __('todos::todos.sort.due_date'),
                        ]"
                    ></x-synapse-select>
                </div>

                {{-- Filter Status --}}
                <div>
                    <label
                        class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400"
                        for="filterCompleted"
                    >{{ __('todos::todos.filters.status') }}</label>
                    <x-synapse-select
                        id="filterCompleted"
                        name="filterCompleted"
                        wire:model.live="filterCompleted"
                        :options="[
                            'all' => __('todos::todos.status.all'),
                            'pending' => __('todos::todos.status.pending'),
                            'completed' => __('todos::todos.status.completed'),
                        ]"
                    ></x-synapse-select>
                </div>

                {{-- Filter Overdue --}}
                <div class="flex items-end">
                    <label class="flex cursor-pointer items-center">
                        <input
                            class="form-check-input mr-2"
                            type="checkbox"
                            wire:model.live="filterOverdue"
                        />
                        <span
                            class="text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('todos::todos.filters.show_overdue') }}</span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    {{-- Todos List --}}
    <div class="space-y-3">
        @forelse ($todos as $todo)
            <div
                class="card"
                wire:key="todo-{{ $todo->id }}"
            >
                <div class="flex items-start gap-4 p-5">
                    {{-- Checkbox --}}
                    <div class="shrink-0 pt-1">
                        <input
                            class="text-brand-500 focus:ring-brand-500 h-5 w-5 rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900"
                            type="checkbox"
                            wire:click="toggleCompleted({{ $todo->id }})"
                            @checked($todo->completed)
                        />
                    </div>

                    {{-- Content --}}
                    <div class="min-w-0 flex-1">
                        <h4
                            class="text-base font-medium text-gray-800 dark:text-white/90"
                            :class="{ 'line-through text-gray-400': {{ $todo->completed ? 'true' : 'false' }} }"
                        >
                            {{ $todo->title }}
                        </h4>

                        @if ($todo->detail)
                            <p
                                class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                                :class="{ 'line-through': {{ $todo->completed ? 'true' : 'false' }} }"
                            >
                                {{ Str::limit($todo->detail, 150) }}
                            </p>
                        @endif

                        {{-- Tags --}}
                        @if ($todo->tag && count($todo->tag) > 0)
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach ($todo->tag as $tag)
                                    <span
                                        class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 dark:bg-blue-900/20 dark:text-blue-400"
                                    >
                                        {{ $tag }}
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        {{-- Due Date --}}
                        @if ($todo->due_date)
                            <div class="mt-2">
                                @php
                                    $isOverdue = !$todo->completed && $todo->due_date->isPast();
                                    $isDueToday = $todo->due_date->isToday();
                                @endphp
                                <span @class([
                                    'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium',
                                    'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-400' => $isOverdue,
                                    'bg-yellow-50 text-yellow-700 dark:bg-yellow-900/20 dark:text-yellow-400' => $isDueToday,
                                    'bg-gray-50 text-gray-600 dark:bg-gray-800 dark:text-gray-400' =>
                                        !$isOverdue && !$isDueToday,
                                ])>
                                    <i class="fa-solid fa-clock mr-1"></i>
                                    {{ $todo->due_date->format('M d, Y H:i') }}
                                    @if ($isOverdue)
                                        ({{ __('todos::todos.list.overdue') }})
                                    @elseif($isDueToday)
                                        ({{ __('todos::todos.list.due_today') }})
                                    @endif
                                </span>
                            </div>
                        @endif

                        {{-- Completed Info --}}
                        @if ($todo->completed && $todo->completed_at)
                            <div class="mt-2">
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    <i class="fa-solid fa-check-circle mr-1"></i>
                                    {{ __('todos::todos.list.completed_at', ['time' => $todo->completed_at->diffForHumans()]) }}
                                </span>
                            </div>
                        @endif
                    </div>

                    {{-- Actions --}}
                    <div class="flex shrink-0 items-center gap-2">
                        <button
                            class="btn-icon primary has-tooltip group"
                            type="button"
                            title="{{ __('todos::todos.tooltips.edit') }}"
                            wire:click="editTodo({{ $todo->id }})"
                        >
                            <span class="fa-solid fa-edit"></span>
                            <span class="tooltip">{{ __('todos::todos.tooltips.edit') }}</span>
                        </button>

                        <button
                            class="btn-icon danger has-tooltip group"
                            type="button"
                            title="{{ __('todos::todos.tooltips.delete') }}"
                            wire:click="$dispatch('confirm-dialog', {
                                title: '{{ __('todos::todos.confirm.delete_title') }}',
                                message: '{{ __('todos::todos.confirm.delete_message', ['title' => addslashes($todo->title)]) }}',
                                confirmText: '{{ __('todos::todos.confirm.delete_confirm') }}',
                                cancelText: '{{ __('todos::todos.confirm.delete_cancel') }}',
                                confirmColor: 'danger',
                                icon: 'fa-solid fa-trash',
                                wireMethod: 'deleteTodo',
                                wireParams: ['{{ $todo->delete_token }}'],
                                wireComponent: '{{ $this->getId() }}'
                            })"
                        >
                            <span class="fa-solid fa-trash"></span>
                            <span class="tooltip">{{ __('todos::todos.tooltips.delete') }}</span>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="card">
                <div class="flex flex-col items-center justify-center p-10">
                    <i class="fa-solid fa-inbox mb-4 text-6xl text-gray-300 dark:text-gray-600"></i>
                    <p class="text-center text-gray-500 dark:text-gray-400">
                        @if ($search || $filterCompleted !== 'all' || $filterOverdue)
                            {{ __('todos::todos.empty.no_results') }}
                        @else
                            {{ __('todos::todos.empty.no_todos') }}
                        @endif
                    </p>
                </div>
            </div>
        @endforelse

        {{-- Infinite Scroll Trigger --}}
        @if ($this->hasMore)
            <div
                class="flex justify-center py-8"
                x-data="{
                    observer: null,
                    init() {
                        this.observer = new IntersectionObserver((entries) => {
                            if (entries[0].isIntersecting) {
                                @this.call('loadMore')
                            }
                        }, {
                            rootMargin: '100px'
                        })
                        this.observer.observe(this.$el)
                    },
                    destroy() {
                        if (this.observer) {
                            this.observer.disconnect()
                        }
                    }
                }"
            >
                <div
                    class="flex items-center gap-2 text-gray-500 dark:text-gray-400"
                    wire:loading
                    wire:target="loadMore"
                >
                    <i class="fa-solid fa-spinner fa-spin"></i>
                    <span>{{ __('todos::todos.list.loading_more') }}</span>
                </div>
            </div>
        @endif
    </div>
</div>
