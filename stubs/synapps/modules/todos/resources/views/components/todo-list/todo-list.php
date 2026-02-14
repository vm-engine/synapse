<?php

declare(strict_types=1);

use Livewire\Attributes\On;
use Livewire\Component;
use SynApps\Modules\Todos\Models\Todo;

new class extends Component
{
    public string $search = '';

    public string $sortBy = 'created_desc';

    public string $filterCompleted = 'all';

    public bool $filterOverdue = false;

    public ?string $filterByDate = null;

    public int $perPage = 20;

    protected bool $hasMoreRecords = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'sortBy' => ['except' => 'created_desc'],
        'filterCompleted' => ['except' => 'all'],
        'filterOverdue' => ['except' => false],
    ];

    public function mount(): void
    {
        // Initialize
    }

    #[On('todo-created')]
    #[On('todo-updated')]
    public function refreshList(): void
    {
        $this->perPage = 20;
    }

    public function loadMore(): void
    {
        $this->perPage += 20;
    }

    public function toggleCompleted(int $id): void
    {
        $todo = Todo::findOrFail($id);
        $todo->completed = ! $todo->completed;
        $todo->completed_at = $todo->completed ? now() : null;
        $todo->save();

        $this->dispatch('notify',
            variant: 'success',
            title: __('todos::todos.notifications.success'),
            message: __('todos::todos.messages.status_updated')
        );
    }

    public function editTodo(int $id): void
    {
        $this->dispatch('edit-todo', id: $id);
    }

    public function deleteTodo(string $token): void
    {
        $id = Todo::validateDeleteToken($token);
        if (! $id) {
            $this->dispatch('notify',
                variant: 'danger',
                title: __('todos::todos.notifications.error'),
                message: __('todos::todos.messages.invalid_delete_token')
            );

            return;
        }
        $todo = Todo::findOrFail($id);
        $todo->delete();

        $this->dispatch('notify',
            variant: 'success',
            title: __('todos::todos.notifications.success'),
            message: __('todos::todos.messages.deleted', ['title' => $todo->title])
        );
    }

    public function updatedSearch(): void
    {
        $this->perPage = 20;
    }

    public function updated($property): void
    {
        if (in_array($property, ['filterCompleted', 'filterOverdue', 'filterByDate', 'sortBy'])) {
            $this->perPage = 20;
        }
    }

    public function getTodosProperty()
    {
        $todos = $this->buildQuery()->limit($this->perPage + 1)->get();
        $this->hasMoreRecords = $todos->count() > $this->perPage;

        return $todos->take($this->perPage);
    }

    public function getHasMoreProperty(): bool
    {
        return $this->hasMoreRecords ?? false;
    }

    protected function buildQuery()
    {
        $query = Todo::query();

        if ($this->search) {
            $query->search($this->search);
        }

        if ($this->filterCompleted === 'completed') {
            $query->completed();
        } elseif ($this->filterCompleted === 'pending') {
            $query->pending();
        }

        if ($this->filterOverdue) {
            $query->overdue();
        }

        if ($this->filterByDate) {
            $query->whereDate('due_date', $this->filterByDate);
        }

        match ($this->sortBy) {
            'created_desc' => $query->orderBy('created_at', 'desc'),
            'created_asc' => $query->orderBy('created_at', 'asc'),
            'due_date' => $query->orderByRaw('due_date IS NULL, due_date ASC'),
            default => $query->orderBy('created_at', 'desc'),
        };

        return $query;
    }
};
