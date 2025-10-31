<?php

namespace SynDB\Modules\Example\Seeders;

use Illuminate\Database\Seeder;
use SynApps\Modules\Example\Models\ExampleCategory;

class ExampleCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ExampleCategory::factory(10)->create();
    }
}
