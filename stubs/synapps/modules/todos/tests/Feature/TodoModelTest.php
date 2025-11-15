<?php

use SynApps\Modules\Todos\Models\Todo;

use function Pest\Laravel\assertDatabaseHas;

test('can create todo with factory', function () {
    $todo = Todo::factory()->create([
        'title' => 'Test Todo',
    ]);

    expect($todo)->toBeInstanceOf(Todo::class)
        ->and($todo->title)->toBe('Test Todo')
        ->and($todo->completed)->toBeFalse();
});

test('casts tag as array', function () {
    $todo = Todo::factory()->create([
        'tag' => ['work', 'urgent'],
    ]);

    expect($todo->tag)->toBeArray()
        ->and($todo->tag)->toBe(['work', 'urgent']);
});

test('casts due_date as datetime', function () {
    $dueDate = now()->addDay();
    $todo = Todo::factory()->create([
        'due_date' => $dueDate,
    ]);

    expect($todo->due_date)->toBeInstanceOf(\Illuminate\Support\Carbon::class)
        ->and($todo->due_date->format('Y-m-d'))->toBe($dueDate->format('Y-m-d'));
});

test('casts completed as boolean', function () {
    $todo = Todo::factory()->create(['completed' => 1]);

    expect($todo->completed)->toBeTrue()
        ->and($todo->completed)->toBeBool();
});

test('casts completed_at as datetime', function () {
    $completedAt = now();
    $todo = Todo::factory()->create([
        'completed' => true,
        'completed_at' => $completedAt,
    ]);

    expect($todo->completed_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

test('completed scope filters completed todos', function () {
    Todo::factory()->create(['title' => 'Completed', 'completed' => true]);
    Todo::factory()->create(['title' => 'Pending', 'completed' => false]);

    $completed = Todo::completed()->get();

    expect($completed)->toHaveCount(1)
        ->and($completed->first()->title)->toBe('Completed');
});

test('pending scope filters pending todos', function () {
    Todo::factory()->create(['title' => 'Completed', 'completed' => true]);
    Todo::factory()->create(['title' => 'Pending', 'completed' => false]);

    $pending = Todo::pending()->get();

    expect($pending)->toHaveCount(1)
        ->and($pending->first()->title)->toBe('Pending');
});

test('overdue scope filters overdue incomplete todos', function () {
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
    Todo::factory()->create([
        'title' => 'Overdue but Completed',
        'due_date' => now()->subDay(),
        'completed' => true,
    ]);

    $overdue = Todo::overdue()->get();

    expect($overdue)->toHaveCount(1)
        ->and($overdue->first()->title)->toBe('Overdue');
});

test('search scope filters by title', function () {
    Todo::factory()->create(['title' => 'Buy groceries']);
    Todo::factory()->create(['title' => 'Walk the dog']);

    $results = Todo::search('groceries')->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->title)->toBe('Buy groceries');
});

test('search scope filters by detail', function () {
    Todo::factory()->create([
        'title' => 'Shopping',
        'detail' => 'Buy milk and eggs',
    ]);
    Todo::factory()->create([
        'title' => 'Exercise',
        'detail' => 'Go for a run',
    ]);

    $results = Todo::search('milk')->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->title)->toBe('Shopping');
});

test('search scope is case insensitive', function () {
    Todo::factory()->create(['title' => 'Buy Groceries']);

    $results = Todo::search('groceries')->get();

    expect($results)->toHaveCount(1);
});

test('search scope searches both title and detail', function () {
    Todo::factory()->create([
        'title' => 'Shopping',
        'detail' => 'Buy groceries',
    ]);
    Todo::factory()->create([
        'title' => 'Buy milk',
        'detail' => 'From the store',
    ]);

    $results = Todo::search('buy')->get();

    expect($results)->toHaveCount(2);
});

test('can store null values for optional fields', function () {
    $todo = Todo::create([
        'title' => 'Test Todo',
        'detail' => null,
        'tag' => null,
        'due_date' => null,
        'completed' => false,
    ]);

    expect($todo->detail)->toBeNull()
        ->and($todo->tag)->toBeNull()
        ->and($todo->due_date)->toBeNull();
});

test('fillable attributes are protected', function () {
    $todo = Todo::factory()->make();

    $fillable = $todo->getFillable();

    expect($fillable)->toContain('title')
        ->and($fillable)->toContain('detail')
        ->and($fillable)->toContain('tag')
        ->and($fillable)->toContain('due_date')
        ->and($fillable)->toContain('completed')
        ->and($fillable)->toContain('completed_at');
});

test('has timestamps', function () {
    $todo = Todo::factory()->create();

    expect($todo->created_at)->not->toBeNull()
        ->and($todo->updated_at)->not->toBeNull();
});

test('factory creates valid todo', function () {
    $todo = Todo::factory()->create();

    expect($todo->title)->not->toBeEmpty()
        ->and($todo->completed)->toBeBool();

    assertDatabaseHas('todos', [
        'id' => $todo->id,
        'title' => $todo->title,
    ]);
});

test('factory can create completed todo', function () {
    $todo = Todo::factory()->completed()->create();

    expect($todo->completed)->toBeTrue()
        ->and($todo->completed_at)->not->toBeNull();
});

test('factory can create pending todo', function () {
    $todo = Todo::factory()->pending()->create();

    expect($todo->completed)->toBeFalse()
        ->and($todo->completed_at)->toBeNull();
});

test('factory can create overdue todo', function () {
    $todo = Todo::factory()->overdue()->create();

    expect($todo->due_date)->not->toBeNull()
        ->and($todo->due_date->isPast())->toBeTrue()
        ->and($todo->completed)->toBeFalse();
});

test('can update todo attributes', function () {
    $todo = Todo::factory()->create([
        'title' => 'Original',
        'completed' => false,
    ]);

    $todo->update([
        'title' => 'Updated',
        'completed' => true,
        'completed_at' => now(),
    ]);

    expect($todo->fresh()->title)->toBe('Updated')
        ->and($todo->fresh()->completed)->toBeTrue();
});

test('can delete todo', function () {
    $todo = Todo::factory()->create();
    $id = $todo->id;

    $todo->delete();

    expect(Todo::find($id))->toBeNull();
});

test('tag attribute accepts empty array', function () {
    $todo = Todo::create([
        'title' => 'Test',
        'tag' => [],
        'completed' => false,
    ]);

    expect($todo->tag)->toBeArray()
        ->and($todo->tag)->toBeEmpty();
});

test('tag attribute accepts multiple values', function () {
    $tags = ['work', 'urgent', 'important', 'deadline'];
    $todo = Todo::create([
        'title' => 'Test',
        'tag' => $tags,
        'completed' => false,
    ]);

    expect($todo->tag)->toBe($tags);
});

test('due_date can be set to future date', function () {
    $futureDate = now()->addMonth();
    $todo = Todo::factory()->create([
        'due_date' => $futureDate,
    ]);

    expect($todo->due_date->isFuture())->toBeTrue();
});

test('completed_at is null for pending todos', function () {
    $todo = Todo::factory()->pending()->create();

    expect($todo->completed_at)->toBeNull();
});

test('completed_at has value for completed todos', function () {
    $todo = Todo::factory()->completed()->create();

    expect($todo->completed_at)->not->toBeNull()
        ->and($todo->completed_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});
