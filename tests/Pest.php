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
