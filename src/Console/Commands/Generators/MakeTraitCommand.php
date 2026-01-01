<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Console\Commands\Generators;

class MakeTraitCommand extends BaseGeneratorCommand
{
    protected $signature = 'synapps:make-trait
                            {module : The module name}
                            {name? : The name of the trait}
                            {--path= : Custom base path for module}
                            ';

    protected $description = 'Create a new trait for a module';

    protected string $componentType = 'trait';
}
