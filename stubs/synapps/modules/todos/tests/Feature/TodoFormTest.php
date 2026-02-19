<?php

use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use SynApps\Modules\Todos\Models\Todo;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function () {
    RateLimiter::clear('todo-create-'.session()->getId());
});

test('can render todo form component', function () {
    Livewire::test('todos.todo-form')
        ->assertStatus(200)
        ->assertSet('title', '')
        ->assertSet('detail', '')
        ->assertSet('tag', [])
        ->assertSet('due_date', null)
        ->assertSet('isEditing', false);
});

test('can create a new todo', function () {
    Livewire::test('todos.todo-form')
        ->set('title', 'Test Todo')
        ->set('detail', 'Test description')
        ->set('tag', ['work', 'urgent'])
        ->set('due_date', '2025-12-31 23:59')
        ->call('save')
        ->assertDispatched('todo-created')
        ->assertDispatched('notify');

    assertDatabaseHas('todos', [
        'title' => 'Test Todo',
        'detail' => 'Test description',
        'completed' => false,
    ]);

    $todo = Todo::where('title', 'Test Todo')->first();
    expect($todo->tag)->toBe(['work', 'urgent']);
});

test('validates required title field', function () {
    Livewire::test('todos.todo-form')
        ->set('title', '')
        ->call('save')
        ->assertHasErrors(['title' => 'required']);

    assertDatabaseMissing('todos', ['title' => '']);
});

test('validates title max length', function () {
    Livewire::test('todos.todo-form')
        ->set('title', str_repeat('a', 256))
        ->call('save')
        ->assertHasErrors(['title' => 'max']);
});

test('  ', function () {
    Livewire::test('todos.todo-form')
        ->set('title', 'Simple Todo')
        ->call('save')
        ->assertDispatched('todo-created');

    assertDatabaseHas('todos', [
        'title' => 'Simple Todo',
        'detail' => null,
        'completed' => 0,
    ]);
});

test('resets form after successful creation', function () {
    Livewire::test('todos.todo-form')
        ->set('title', 'Test Todo')
        ->set('detail', 'Some detail')
        ->call('save')
        ->assertSet('title', '')
        ->assertSet('detail', '')
        ->assertSet('tag', [])
        ->assertSet('due_date', null)
        ->assertDispatched('reset-tag-input');
});

test('can edit existing todo', function () {
    $todo = Todo::create([
        'title' => 'Original Title',
        'detail' => 'Original detail',
        'tag' => ['tag1'],
        'due_date' => now()->addDay(),
        'completed' => false,
    ]);

    Livewire::test('todos.todo-form')
        ->call('editTodo', $todo->id)
        ->assertSet('todoId', $todo->id)
        ->assertSet('title', 'Original Title')
        ->assertSet('detail', 'Original detail')
        ->assertSet('tag', ['tag1'])
        ->assertSet('isEditing', true)
        ->assertDispatched('scroll-to-form');
});

test('can update existing todo', function () {
    $todo = Todo::create([
        'title' => 'Original Title',
        'detail' => 'Original detail',
        'completed' => false,
    ]);

    Livewire::test('todos.todo-form')
        ->call('editTodo', $todo->id)
        ->set('title', 'Updated Title')
        ->set('detail', 'Updated detail')
        ->set('tag', ['updated'])
        ->call('save')
        ->assertDispatched('todo-updated')
        ->assertDispatched('notify');

    assertDatabaseHas('todos', [
        'id' => $todo->id,
        'title' => 'Updated Title',
        'detail' => 'Updated detail',
    ]);

    expect($todo->fresh()->tag)->toBe(['updated']);
});

test('can cancel editing', function () {
    $todo = Todo::create([
        'title' => 'Test Todo',
        'completed' => false,
    ]);

    Livewire::test('todos.todo-form')
        ->call('editTodo', $todo->id)
        ->assertSet('isEditing', true)
        ->call('cancelEdit')
        ->assertSet('isEditing', false)
        ->assertSet('title', '')
        ->assertSet('todoId', null);
});

test('can reset form', function () {
    Livewire::test('todos.todo-form')
        ->set('title', 'Some Title')
        ->set('detail', 'Some Detail')
        ->call('resetForm')
        ->assertSet('title', '')
        ->assertSet('detail', '');
});

test('throttles todo creation to once per 5 seconds', function () {
    $component = Livewire::test('todos.todo-form');

    // First submission should succeed
    $component
        ->set('title', 'First Todo')
        ->call('save')
        ->assertDispatched('todo-created');

    // Second submission within 5 seconds should be throttled
    $component
        ->set('title', 'Second Todo')
        ->call('save')
        ->assertDispatched('notify');

    // Should only have one todo
    expect(Todo::count())->toBe(1);
});

test('can edit todo even after creating one', function () {
    $todo = Todo::create([
        'title' => 'Original Title',
        'completed' => false,
    ]);

    $component = Livewire::test('todos.todo-form');

    // Create a todo first to trigger rate limit
    $component
        ->set('title', 'New Todo')
        ->call('save')
        ->assertDispatched('todo-created');

    // Now try to edit - should work because updateTodo doesn't hit rate limiter
    $component
        ->call('editTodo', $todo->id)
        ->set('title', 'Updated Title')
        ->call('save')
        ->assertDispatched('todo-updated');

    assertDatabaseHas('todos', [
        'id' => $todo->id,
        'title' => 'Updated Title',
    ]);
});

test('validates due_date format', function () {
    Livewire::test('todos.todo-form')
        ->set('title', 'Test Todo')
        ->set('due_date', 'invalid-date')
        ->call('save')
        ->assertHasErrors(['due_date' => 'date']);
});

test('accepts null values for optional fields', function () {
    Livewire::test('todos.todo-form')
        ->set('title', 'Test Todo')
        ->set('detail', null)
        ->set('tag', [])
        ->set('due_date', null)
        ->call('save')
        ->assertHasNoErrors();

    assertDatabaseHas('todos', [
        'title' => 'Test Todo',
        'detail' => null,
        'due_date' => null,
    ]);
});
