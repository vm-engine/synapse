<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Console\Commands\Generators;

class MakeJobMiddlewareCommand extends BaseGeneratorCommand
{
    protected $hidden = true;

    protected $signature = 'synapps:make-job-middleware
                            {module : The module name}
                            {name? : The name of the job middleware}
                            {--path= : Custom base path for module}
                            ';

    protected $description = 'Create a new job middleware class for a module';

    protected string $componentType = 'job-middleware';
}
