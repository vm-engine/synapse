<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Console\Commands\Generators;

class MakeJobCommand extends BaseGeneratorCommand
{
    protected $signature = 'synapps:make-job
                            {module : The module name}
                            {name? : The name of the job}
                            {--path= : Custom base path for module}
                            ';

    protected $description = 'Create a new job class for a module';

    protected string $componentType = 'job';
}
