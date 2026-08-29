<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Console\Commands\Generators;

class MakeRequestCommand extends BaseGeneratorCommand
{
    protected $hidden = true;

    protected $signature = 'synapps:make-request
                            {module : The module name}
                            {name? : The name of the request}
                            {--path= : Custom base path for module}
                            ';

    protected $description = 'Create a new form request class for a module';

    protected string $componentType = 'request';
}
