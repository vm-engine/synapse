<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Console\Commands\Generators;

class MakeTestCommand extends BaseGeneratorCommand
{
    protected $hidden = true;

    protected $signature = 'synapps:make-test
                            {module : The module name}
                            {name? : The name of the test}
                            {--unit : Create unit test instead of feature test}
                            {--path= : Custom base path for module}
                            ';

    protected $description = 'Create a new test class for a module';

    protected string $componentType = 'test';

    /**
     * @return array<string, mixed>
     */
    protected function getComponentOptions(): array
    {
        return [
            'unit' => $this->option('unit'),
        ];
    }
}
