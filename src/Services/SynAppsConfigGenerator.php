<?php

namespace VmEngine\Synapse\Services;

use VmEngine\Synapse\Services\ConfigGenerator\Builder;

class SynAppsConfigGenerator
{
    public static function generate()
    {

        return (new Builder())->generate();
    }
}
