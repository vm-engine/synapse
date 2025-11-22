<?php

namespace VmEngine\Synapse\Services;

use VmEngine\Synapse\Services\ConfigGenerator\Builder;

class SynAppsConfigGenerator
{
    public static function generate()
    {
        return (new Builder)->generate();
    }

    public static function config()
    {
        if (! file_exists(synapps_path('config/compiled/synapps.json'))) {
            return (new Builder)->generate();
        }

        return json_decode(
            file_get_contents(synapps_path('config/compiled/synapps.json')),
            true
        );
    }
}
