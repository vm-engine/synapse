<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Console\Commands\Generators;

class MakeModelCommand extends BaseGeneratorCommand
{
    protected $hidden = true;

    protected $signature = 'synapps:make-model
                            {module : The module name}
                            {name? : The name of the model}
                            {--m|migration : Create migration file}
                            {--f|factory : Create factory file}
                            {--s|seeder : Create seeder file}
                            {--a|all : Create migration, factory, and seeder}
                            {--pivot : Create pivot model}
                            {--path= : Custom base path for module}
                            ';

    protected $description = 'Create a new model class for a module';

    protected string $componentType = 'model';

    /**
     * @return array<string, mixed>
     */
    protected function getComponentOptions(): array
    {
        $options = [
            'pivot' => $this->option('pivot'),
        ];

        // Handle --all flag
        if ($this->option('all')) {
            $this->createMigration();
            $this->createFactory();
            $this->createSeeder();
        } else {
            if ($this->option('migration')) {
                $this->createMigration();
            }

            if ($this->option('factory')) {
                $this->createFactory();
            }

            if ($this->option('seeder')) {
                $this->createSeeder();
            }
        }

        return $options;
    }

    protected function createMigration(): void
    {
        $name = $this->stringArgument('name');
        $tableName = $this->generator->namespaceResolver->generateTableName($name);

        $this->call('synapps:make-migration', [
            'module' => $this->argument('module'),
            'name' => 'create_'.$tableName.'_table',
            '--create' => $tableName,
            '--no-interaction' => true,
        ]);
    }

    protected function createFactory(): void
    {
        $this->call('synapps:make-factory', [
            'module' => $this->argument('module'),
            'name' => $this->stringArgument('name').'Factory',
            '--model' => $this->argument('name'),
            '--no-interaction' => true,
        ]);
    }

    protected function createSeeder(): void
    {
        $this->call('synapps:make-seeder', [
            'module' => $this->argument('module'),
            'name' => $this->stringArgument('name').'Seeder',
            '--no-interaction' => true,
        ]);
    }
}
