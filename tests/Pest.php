<?php

declare(strict_types=1);

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Pest Configuration
|--------------------------------------------------------------------------
|
| Configure Pest to use Orchestra Testbench for Laravel package testing.
|
*/

uses(TestCase::class)->in('Feature', 'Unit');

/**
 * Enable the opt-in searchable feature for a test by running its migration.
 */
function setUpSearchable(): void
{
    test()->artisan('migrate', [
        '--path' => realpath(__DIR__.'/../database/migrations/searchable'),
        '--realpath' => true,
    ])->assertExitCode(0);
}
