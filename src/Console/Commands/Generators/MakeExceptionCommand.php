<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Console\Commands\Generators;

class MakeExceptionCommand extends BaseGeneratorCommand
{
    protected $signature = 'synapps:make-exception
                            {module : The module name}
                            {name? : The name of the exception}
                            {--render : Create the exception with an empty render method}
                            {--report : Create the exception with an empty report method}
                            {--path= : Custom base path for module}
                            ';

    protected $description = 'Create a new custom exception class for a module';

    protected string $componentType = 'exception';

    /**
     * @return array<string, mixed>
     */
    protected function getComponentOptions(): array
    {
        return [
            'render' => $this->option('render'),
            'report' => $this->option('report'),
        ];
    }
}
