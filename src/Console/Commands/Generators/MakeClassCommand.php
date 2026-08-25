<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Console\Commands\Generators;

class MakeClassCommand extends BaseGeneratorCommand
{
    protected $signature = 'synapps:make-class
                            {module : The module name}
                            {name? : The name of the class}
                            {--invokable : Generate a single method, invokable class}
                            {--path= : Custom base path for module}
                            ';

    protected $description = 'Create a new class for a module';

    protected string $componentType = 'class';

    /**
     * @return array<string, mixed>
     */
    protected function getComponentOptions(): array
    {
        return [
            'invokable' => $this->option('invokable'),
        ];
    }
}
