<?php

declare(strict_types=1);

namespace SynDB\Modules\Todos\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use SynApps\Modules\Todos\Models\Todo;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\SynApps\Modules\Todos\Models\Todo>
 */
class TodoFactory extends Factory
{
    protected $model = Todo::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tags = ['Work', 'Personal', 'Urgent', 'Shopping', 'Health', 'Learning', 'Project'];
        $completed = fake()->boolean(30); // 30% chance of being completed

        return [
            'title' => fake()->sentence(rand(3, 8)),
            'detail' => fake()->optional(0.7)->paragraph(),
            'tag' => fake()->randomElements($tags, rand(0, 3)),
            'due_date' => fake()->optional(0.6)->dateTimeBetween('-7 days', '+30 days'),
            'completed' => $completed,
            'completed_at' => $completed ? fake()->dateTimeBetween('-14 days', 'now') : null,
        ];
    }

    /**
     * Indicate that the todo is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'completed' => true,
            'completed_at' => fake()->dateTimeBetween('-14 days', 'now'),
        ]);
    }

    /**
     * Indicate that the todo is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'completed' => false,
            'completed_at' => null,
        ]);
    }

    /**
     * Indicate that the todo is overdue.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'completed' => false,
            'completed_at' => null,
            'due_date' => fake()->dateTimeBetween('-14 days', '-1 day'),
        ]);
    }
}
