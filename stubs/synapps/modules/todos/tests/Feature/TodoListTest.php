<?php

use Livewire\Livewire;
use SynApps\Modules\Todos\Livewire\TodoList;
use SynApps\Modules\Todos\Models\Todo;

use function Pest\Laravel\assertDatabaseHas;

test('can render todo list component', function () {
    Livewire::test(TodoList::class)
        ->assertStatus(200)
        ->assertSet('search', '')
        ->assertSet('sortBy', 'created_desc')
        ->assertSet('filterCompleted', 'all')
        ->assertSet('filterOverdue', false)
        ->assertSet('perPage', 20);
});

test('displays all todos by default', function () {
    Todo::factory()->count(5)->create();

    Livewire::test(TodoList::class)
        ->assertSee(Todo::first()->title)
        ->assertSee(Todo::latest()->first()->title);
});

test('can search todos by title', function () {
    Todo::factory()->create(['title' => 'Buy groceries']);
    Todo::factory()->create(['title' => 'Walk the dog']);

    Livewire::test(TodoList::class)
        ->set('search', 'groceries')
        ->assertSee('Buy groceries')
        ->assertDontSee('Walk the dog');
});

test('can search todos by detail', function () {
    Todo::factory()->create([
        'title' => 'Shopping',
        'detail' => 'Buy milk and eggs',
    ]);
    Todo::factory()->create([
        'title' => 'Exercise',
        'detail' => 'Go for a run',
    ]);

    Livewire::test(TodoList::class)
        ->set('search', 'milk')
        ->assertSee('Shopping')
        ->assertDontSee('Exercise');
});

test('can sort todos by newest first', function () {
    $older = Todo::factory()->create(['created_at' => now()->subDay()]);
    $newer = Todo::factory()->create(['created_at' => now()]);

    $component = Livewire::test(TodoList::class)
        ->set('sortBy', 'created_desc');

    $todos = $component->get('todos');
    expect($todos->first()->id)->toBe($newer->id);
});

test('can sort todos by oldest first', function () {
    $older = Todo::factory()->create(['created_at' => now()->subDay()]);
    $newer = Todo::factory()->create(['created_at' => now()]);

    $component = Livewire::test(TodoList::class)
        ->set('sortBy', 'created_asc');

    $todos = $component->get('todos');
    expect($todos->first()->id)->toBe($older->id);
});

test('can sort todos by due date', function () {
    $noDueDate = Todo::factory()->create(['due_date' => null]);
    $laterDate = Todo::factory()->create(['due_date' => now()->addWeek()]);
    $earlierDate = Todo::factory()->create(['due_date' => now()->addDay()]);

    $component = Livewire::test(TodoList::class)
        ->set('sortBy', 'due_date');

    $todos = $component->get('todos');
    expect($todos->first()->id)->toBe($earlierDate->id);
    expect($todos->last()->id)->toBe($noDueDate->id);
});

test('can filter completed todos', function () {
    Todo::factory()->create(['title' => 'Done Task XYZ', 'completed' => true]);
    Todo::factory()->create(['title' => 'Incomplete Task ABC', 'completed' => false]);

    Livewire::test(TodoList::class)
        ->set('filterCompleted', 'completed')
        ->assertSee('Done Task XYZ')
        ->assertDontSee('Incomplete Task ABC');
});

test('can filter pending todos', function () {
    Todo::factory()->create(['title' => 'Done Task XYZ', 'completed' => true]);
    Todo::factory()->create(['title' => 'Incomplete Task ABC', 'completed' => false]);

    Livewire::test(TodoList::class)
        ->set('filterCompleted', 'pending')
        ->assertSee('Incomplete Task ABC')
        ->assertDontSee('Done Task XYZ');
});

test('can filter overdue todos', function () {
    Todo::factory()->create([
        'title' => 'Overdue',
        'due_date' => now()->subDay(),
        'completed' => false,
    ]);
    Todo::factory()->create([
        'title' => 'Not Overdue',
        'due_date' => now()->addDay(),
        'completed' => false,
    ]);

    Livewire::test(TodoList::class)
        ->set('filterOverdue', true)
        ->assertSee('Overdue')
        ->assertDontSee('Not Overdue');
});

test('can toggle todo completion status', function () {
    $todo = Todo::factory()->create(['completed' => false]);

    Livewire::test(TodoList::class)
        ->call('toggleCompleted', $todo->id)
        ->assertDispatched('notify');

    assertDatabaseHas('todos', [
        'id' => $todo->id,
        'completed' => true,
    ]);

    expect($todo->fresh()->completed_at)->not->toBeNull();
});

test('can toggle todo from completed to pending', function () {
    $todo = Todo::factory()->create([
        'completed' => true,
        'completed_at' => now(),
    ]);

    Livewire::test(TodoList::class)
        ->call('toggleCompleted', $todo->id);

    assertDatabaseHas('todos', [
        'id' => $todo->id,
        'completed' => false,
    ]);

    expect($todo->fresh()->completed_at)->toBeNull();
});

test('can dispatch edit todo event', function () {
    $todo = Todo::factory()->create();

    Livewire::test(TodoList::class)
        ->call('editTodo', $todo->id)
        ->assertDispatched('edit-todo', id: $todo->id);
});

test('can delete todo', function () {
    $todo = Todo::factory()->create(['title' => 'To Delete']);

    Livewire::test(TodoList::class)
        ->call('deleteTodo', $todo->delete_token)
        ->assertDispatched('notify');

    expect(Todo::find($todo->id))->toBeNull();
});

test('loads 20 todos initially', function () {
    Todo::factory()->count(30)->create();

    $component = Livewire::test(TodoList::class);
    $todos = $component->get('todos');

    expect($todos->count())->toBe(20);
});

test('can load more todos with infinite scroll', function () {
    Todo::factory()->count(30)->create();

    $component = Livewire::test(TodoList::class)
        ->assertSet('perPage', 20);

    $component->call('loadMore')
        ->assertSet('perPage', 40);

    $todos = $component->get('todos');
    expect($todos->count())->toBe(30); // All 30 loaded
});

test('hasMore property is true when there are more records', function () {
    Todo::factory()->count(25)->create();

    $component = Livewire::test(TodoList::class);
    expect($component->get('hasMore'))->toBeTrue();
});

test('hasMore property is false when all records are loaded', function () {
    Todo::factory()->count(15)->create();

    $component = Livewire::test(TodoList::class);
    expect($component->get('hasMore'))->toBeFalse();
});

test('resets perPage when search changes', function () {
    Todo::factory()->count(30)->create();

    Livewire::test(TodoList::class)
        ->call('loadMore') // Increase to 40
        ->assertSet('perPage', 40)
        ->set('search', 'test')
        ->assertSet('perPage', 20);
});

test('resets perPage when filters change', function () {
    Todo::factory()->count(30)->create();

    Livewire::test(TodoList::class)
        ->call('loadMore')
        ->assertSet('perPage', 40)
        ->set('filterCompleted', 'completed')
        ->assertSet('perPage', 20);
});

test('resets perPage when sort changes', function () {
    Todo::factory()->count(30)->create();

    Livewire::test(TodoList::class)
        ->call('loadMore')
        ->assertSet('perPage', 40)
        ->set('sortBy', 'due_date')
        ->assertSet('perPage', 20);
});

test('refreshes list when todo is created', function () {
    Livewire::test(TodoList::class)
        ->assertSet('perPage', 20)
        ->call('loadMore')
        ->assertSet('perPage', 40)
        ->dispatch('todo-created')
        ->assertSet('perPage', 20);
});

test('refreshes list when todo is updated', function () {
    Livewire::test(TodoList::class)
        ->assertSet('perPage', 20)
        ->call('loadMore')
        ->assertSet('perPage', 40)
        ->dispatch('todo-updated')
        ->assertSet('perPage', 20);
});

test('can combine search and filters', function () {
    Todo::factory()->create([
        'title' => 'Buy groceries',
        'completed' => false,
    ]);
    Todo::factory()->create([
        'title' => 'Buy milk',
        'completed' => true,
    ]);
    Todo::factory()->create([
        'title' => 'Walk dog',
        'completed' => false,
    ]);

    Livewire::test(TodoList::class)
        ->set('search', 'buy')
        ->set('filterCompleted', 'pending')
        ->assertSee('Buy groceries')
        ->assertDontSee('Buy milk')
        ->assertDontSee('Walk dog');
});

test('overdue filter only shows incomplete overdue todos', function () {
    Todo::factory()->create([
        'title' => 'Overdue Incomplete',
        'due_date' => now()->subDay(),
        'completed' => false,
    ]);
    Todo::factory()->create([
        'title' => 'Overdue Complete',
        'due_date' => now()->subDay(),
        'completed' => true,
    ]);

    Livewire::test(TodoList::class)
        ->set('filterOverdue', true)
        ->assertSee('Overdue Incomplete')
        ->assertDontSee('Overdue Complete');
});

test('query string persists search parameter', function () {
    Livewire::withQueryParams(['search' => 'groceries'])
        ->test(TodoList::class)
        ->assertSet('search', 'groceries');
});

test('query string persists sort parameter', function () {
    Livewire::withQueryParams(['sortBy' => 'due_date'])
        ->test(TodoList::class)
        ->assertSet('sortBy', 'due_date');
});

test('query string persists filter parameters', function () {
    Livewire::withQueryParams([
        'filterCompleted' => 'completed',
        'filterOverdue' => '1',
    ])
        ->test(TodoList::class)
        ->assertSet('filterCompleted', 'completed')
        ->assertSet('filterOverdue', true);
});
