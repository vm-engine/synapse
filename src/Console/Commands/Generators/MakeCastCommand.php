<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Console\Commands\Generators;

class MakeCastCommand extends BaseGeneratorCommand
{
    protected $signature = 'synapps:make-cast
                            {module : The module name}
                            {name? : The name of the cast}
                            {--path= : Custom base path for module}
                            ';

    protected $description = 'Create a new custom Eloquent cast class for a module';

    protected string $componentType = 'cast';
}
