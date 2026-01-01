<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Console\Commands\Generators;

class MakeRuleCommand extends BaseGeneratorCommand
{
    protected $signature = 'synapps:make-rule
                            {module : The module name}
                            {name? : The name of the rule}
                            {--path= : Custom base path for module}
                            ';

    protected $description = 'Create a new validation rule for a module';

    protected string $componentType = 'rule';
}
