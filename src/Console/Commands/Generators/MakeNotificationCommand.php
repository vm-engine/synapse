<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Console\Commands\Generators;

class MakeNotificationCommand extends BaseGeneratorCommand
{
    protected $signature = 'synapps:make-notification
                            {module : The module name}
                            {name? : The name of the notification}
                            {--path= : Custom base path for module}
                            ';

    protected $description = 'Create a new notification class for a module';

    protected string $componentType = 'notification';
}
