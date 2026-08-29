<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Console\Commands\Generators;

class MakeMailCommand extends BaseGeneratorCommand
{
    protected $hidden = true;

    protected $signature = 'synapps:make-mail
                            {module : The module name}
                            {name? : The name of the mailable}
                            {--path= : Custom base path for module}
                            ';

    protected $description = 'Create a new mailable class for a module';

    protected string $componentType = 'mail';
}
