<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Facades;

use Illuminate\Support\Facades\Facade;

class SyNav extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'synav';
    }
}
