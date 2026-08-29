<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Console\Commands\Generators;

class MakePolicyCommand extends BaseGeneratorCommand
{
    protected $hidden = true;

    protected $signature = 'synapps:make-policy
                            {module : The module name}
                            {name? : The name of the policy}
                            {--path= : Custom base path for module}
                            ';

    protected $description = 'Create a new policy class for a module';

    protected string $componentType = 'policy';
}
