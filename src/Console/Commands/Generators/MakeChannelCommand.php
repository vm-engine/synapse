<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Console\Commands\Generators;

class MakeChannelCommand extends BaseGeneratorCommand
{
    protected $signature = 'synapps:make-channel
                            {module : The module name}
                            {name? : The name of the channel}
                            {--path= : Custom base path for module}
                            ';

    protected $description = 'Create a new channel class for a module';

    protected string $componentType = 'channel';
}
