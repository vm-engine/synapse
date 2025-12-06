<?php

namespace VmEngine\Synapse\Facades;

use Illuminate\Support\Facades\Facade;

class DbConfig extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'dbconf';
    }
}
