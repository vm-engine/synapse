<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Console\Commands\Generators;

class MakeMigrationCommand extends BaseGeneratorCommand
{
    protected $signature = 'synapps:make-migration
                            {module : The module name}
                            {name? : The name of the migration}
                            {--create= : Table to create}
                            {--table= : Table to modify}
                            {--path= : Custom base path for module}
                            ';

    protected $description = 'Create a new migration file for a module';

    protected string $componentType = 'migration';

    /**
     * @return array<string, mixed>
     */
    protected function getComponentOptions(): array
    {
        return [
            'create' => $this->option('create'),
            'table' => $this->option('table'),
        ];
    }
}
