<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Console\Commands\Generators;

class MakeConfigCommand extends BaseGeneratorCommand
{
    protected $hidden = true;

    protected $signature = 'synapps:make-config
                            {module : The module name}
                            {name? : The name of the config file}
                            {--path= : Custom base path for module}
                            ';

    protected $description = 'Create a new config file for a module';

    protected string $componentType = 'config';

    protected string $fileExtension = '.php';
}
