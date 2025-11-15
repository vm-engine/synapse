<?php

declare(strict_types=1);

namespace SynApps\Modules\Todos\Livewire;

use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\On;
use Livewire\Component;
use SynApps\Modules\Todos\Models\Todo;

class TodoForm extends Component
{
    public ?int $todoId = null;

    public string $title = '';

    public ?string $detail = null;

    public array $tag = [];

    public ?string $due_date = null;

    public bool $isEditing = false;

    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'detail' => ['nullable', 'string'],
            'tag' => ['nullable', 'array'],
            'due_date' => ['nullable', 'date'],
        ];
    }

    public function save(): void
    {
        $this->validate();

        if ($this->isEditing && $this->todoId) {
            $this->updateTodo();
        } else {
            // Rate limiting: 1 submission per 5 seconds per session (only for creation)
            $key = 'todo-create-'.session()->getId();

            if (RateLimiter::tooManyAttempts($key, 1)) {
                $seconds = RateLimiter::availableIn($key);
                $this->dispatch('notify',
                    variant: 'warning',
                    title: __('todos::todos.notifications.warning'),
                    message: __('todos::todos.messages.throttle', ['seconds' => $seconds])
                );

                return;
            }

            RateLimiter::hit($key, 5); // Lock for 5 seconds
            $this->createTodo();
        }
    }

    protected function createTodo(): void
    {
        Todo::create([
            'title' => $this->title,
            'detail' => $this->detail,
            'tag' => $this->tag,
            'due_date' => $this->due_date,
            'completed' => false,
        ]);

        $this->dispatch('todo-created');
        $this->dispatch('notify',
            variant: 'success',
            title: __('todos::todos.notifications.success'),
            message: __('todos::todos.messages.created')
        );
        $this->reset();
        $this->dispatch('reset-tag-input');
    }

    protected function updateTodo(): void
    {
        $todo = Todo::findOrFail($this->todoId);

        $todo->update([
            'title' => $this->title,
            'detail' => $this->detail,
            'tag' => $this->tag,
            'due_date' => $this->due_date,
        ]);

        $this->dispatch('todo-updated');
        $this->dispatch('notify',
            variant: 'success',
            title: __('todos::todos.notifications.success'),
            message: __('todos::todos.messages.updated')
        );
        $this->cancelEdit();
    }

    #[On('edit-todo')]
    public function editTodo(int $id): void
    {
        $todo = Todo::findOrFail($id);

        $this->todoId = $todo->id;
        $this->title = $todo->title;
        $this->detail = $todo->detail ?? '';
        $this->tag = $todo->tag ?? [];
        $this->due_date = $todo->due_date?->format('Y-m-d H:i');
        $this->isEditing = true;

        // Dispatch event to scroll to form and focus
        $this->dispatch('scroll-to-form');
    }

    public function resetForm(): void
    {
        if ($this->isEditing) {
            // Reset to original values when editing
            $this->editTodo($this->todoId);
        } else {
            // Clear form when adding new
            $this->reset();
        }
    }

    public function cancelEdit(): void
    {
        $this->reset();
        $this->isEditing = false;
    }

    public function render()
    {
        return view('todos::livewire.todo-form');
    }
}
