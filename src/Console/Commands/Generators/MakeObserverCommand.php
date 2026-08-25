<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Console\Commands\Generators;

class MakeObserverCommand extends BaseGeneratorCommand
{
    protected $signature = 'synapps:make-observer
                            {module : The module name}
                            {name? : The name of the observer}
                            {--model= : The model that the observer applies to}
                            {--path= : Custom base path for module}
                            ';

    protected $description = 'Create a new observer class for a module';

    protected string $componentType = 'observer';

    /**
     * @return array<string, mixed>
     */
    protected function getComponentOptions(): array
    {
        // Model resolution (short name -> module's Models namespace, or a given
        // fully-qualified class name used as-is) and the resulting `modelImport`/
        // `modelVariable` stub variables are computed centrally in
        // ModuleGeneratorService::prepareVariables() — see its "Model" section.
        return [
            'model' => $this->option('model'),
        ];
    }
}
