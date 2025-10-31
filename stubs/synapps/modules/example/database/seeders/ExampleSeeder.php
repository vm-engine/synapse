<?php

namespace SynDB\Modules\Example\Seeders;

use Illuminate\Database\Seeder;
use SynApps\Modules\Example\Models\Example;

class ExampleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Example::factory(100)->create();
    }
}
