<?php

namespace VmEngine\Synapse\Facades;

use Illuminate\Support\Facades\Facade;

class DbLang extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'dblang';
    }
}
