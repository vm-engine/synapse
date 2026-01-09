<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Console\Commands\Generators;

class MakeCommandCommand extends BaseGeneratorCommand
{
    protected $signature = 'synapps:make-command
                            {module : The module name}
                            {name? : The name of the command}
                            {--command= : The terminal command name}
                            {--path= : Custom base path for module}
                            ';

    protected $description = 'Create a new Artisan command for a module';

    protected string $componentType = 'command';

    protected string $componentName = '';

    protected function getComponentOptions(): array
    {
        return [
            'command' => $this->option('command') ?: strtolower(str_replace(['\\', '/'], ':', $this->componentName)),
        ];
    }
}
