<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Console\Commands\Generators;

class MakeFactoryCommand extends BaseGeneratorCommand
{
    protected $signature = 'synapps:make-factory
                            {module : The module name}
                            {name? : The name of the factory}
                            {--model= : Model to generate factory for}
                            {--path= : Custom base path for module}
                            ';

    protected $description = 'Create a new factory class for a module';

    protected string $componentType = 'factory';

    /**
     * @return array<string, mixed>
     */
    protected function getComponentOptions(): array
    {
        return [
            'model' => $this->option('model'),
        ];
    }
}
