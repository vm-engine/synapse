<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Console\Commands\Generators;

class MakeResourceCommand extends BaseGeneratorCommand
{
    protected $hidden = true;

    protected $signature = 'synapps:make-resource
                            {module : The module name}
                            {name? : The name of the resource}
                            {--collection : Create a resource collection}
                            {--path= : Custom base path for module}
                            ';

    protected $description = 'Create a new resource for a module';

    protected string $componentType = 'resource';

    /**
     * @return array<string, mixed>
     */
    protected function getComponentOptions(): array
    {
        return [
            'collection' => $this->option('collection'),
        ];
    }
}
