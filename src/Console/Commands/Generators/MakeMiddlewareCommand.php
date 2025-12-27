<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Console\Commands\Generators;

class MakeMiddlewareCommand extends BaseGeneratorCommand
{
    protected $signature = 'synapps:make-middleware
                            {module : The module name}
                            {name? : The name of the middleware}
                            {--path= : Custom base path for module}
                            ';

    protected $description = 'Create a new middleware class for a module';

    protected string $componentType = 'middleware';
}
