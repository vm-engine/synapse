<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Console\Commands\Generators;

class MakeScopeCommand extends BaseGeneratorCommand
{
    protected $hidden = true;

    protected $signature = 'synapps:make-scope
                            {module : The module name}
                            {name? : The name of the scope}
                            {--path= : Custom base path for module}
                            ';

    protected $description = 'Create a new scope for a module';

    protected string $componentType = 'scope';
}
