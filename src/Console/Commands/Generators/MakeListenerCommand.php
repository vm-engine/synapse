<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Console\Commands\Generators;

class MakeListenerCommand extends BaseGeneratorCommand
{
    protected $hidden = true;

    protected $signature = 'synapps:make-listener
                            {module : The module name}
                            {name? : The name of the listener}
                            {--path= : Custom base path for module}
                            ';

    protected $description = 'Create a new event listener class for a module';

    protected string $componentType = 'listener';
}
