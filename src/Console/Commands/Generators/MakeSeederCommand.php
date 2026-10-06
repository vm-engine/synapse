<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Console\Commands\Generators;

class MakeSeederCommand extends BaseGeneratorCommand
{
    protected $hidden = true;

    protected $signature = 'synapps:make-seeder
                            {module : The module name}
                            {name? : The name of the seeder}
                            {--path= : Custom base path for module}
                            ';

    protected $description = 'Create a new seeder class for a module';

    protected string $componentType = 'seeder';
}
