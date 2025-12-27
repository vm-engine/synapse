<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Console\Commands\Generators;

class MakeProviderCommand extends BaseGeneratorCommand
{
    protected $signature = 'synapps:make-provider
                            {module : The module name}
                            {name? : The name of the provider}
                            {--path= : Custom base path for module}
                            ';

    protected $description = 'Create a new service provider class for a module';

    protected string $componentType = 'provider';
}
