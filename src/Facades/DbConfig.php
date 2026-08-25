<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Facades;

use Illuminate\Support\Facades\Facade;

class DbConfig extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'dbconf';
    }
}
