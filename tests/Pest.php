<?php

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
