<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Services;

use VmEngine\Synapse\Services\ConfigGenerator\Builder;

class SynAppsConfigGenerator
{
    /** @return array<string, mixed> */
    public static function generate(): array
    {
        return (new Builder)->generate();
    }

    /** @return array<string, mixed> */
    public static function config(): array
    {
        if (! file_exists(synapps_path('config/compiled/synapps.json'))) {
            return (new Builder)->generate();
        }

        return read_json_file(synapps_path('config/compiled/synapps.json'));
    }
}
