<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Console\Commands\Generators;

class MakeControllerCommand extends BaseGeneratorCommand
{
    protected $signature = 'synapps:make-controller
                            {module : The module name}
                            {name? : The name of the controller}
                            {--m|model= : Generate resource controller for model}
                            {--r|resource : Generate resource controller}
                            {--api : Generate API controller}
                            {--invokable : Generate invokable controller}
                            {--path= : Custom base path for module}
                            ';

    protected $description = 'Create a new controller class for a module';

    protected string $componentType = 'controller';

    protected function getComponentOptions(): array
    {
        return [
            'model' => $this->option('model'),
            'resource' => $this->option('resource'),
            'api' => $this->option('api'),
            'invokable' => $this->option('invokable'),
        ];
    }
}
