<?php

declare(strict_types=1);

namespace SynDB\Modules\Todos\Seeders;

use Illuminate\Database\Seeder;
use SynApps\Modules\Todos\Models\Todo;

class TodoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 5 pending todos
        Todo::factory()->pending()->count(5)->create();

        // Create 3 completed todos
        Todo::factory()->completed()->count(3)->create();

        // Create 2 overdue todos
        Todo::factory()->overdue()->count(2)->create();

        // Create some random todos
        Todo::factory()->count(10)->create();
    }
}
