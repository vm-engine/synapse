<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Console\Commands\Generators;

class MakeViewCommand extends BaseGeneratorCommand
{
    protected $hidden = true;

    protected $signature = 'synapps:make-view
                            {module : The module name}
                            {name? : The name of the view}
                            {--path= : Custom base path for module}
                            ';

    protected $description = 'Create a new view file for a module';

    protected string $componentType = 'view';

    protected string $fileExtension = '.blade.php';
}
