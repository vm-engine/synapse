<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Console\Commands\Generators;

class MakeLivewireCommand extends BaseGeneratorCommand
{
    protected $signature = 'synapps:make-livewire
                            {module : The module name}
                            {name? : The name of the Livewire component}
                            {--inline : Create inline component}
                            {--form : Create form object}
                            {--test : Create Pest test}
                            {--path= : Custom base path for module}
                            ';

    protected $description = 'Create a new Livewire component for a module';

    protected string $componentType = 'livewire';

    public function handle(): int
    {
        $result = parent::handle();

        if ($result === 0 && ! $this->option('inline')) {
            // Create view file for non-inline components
            $this->createView();
        }

        if ($result === 0 && $this->option('test')) {
            $this->createTest();
        }

        return $result;
    }

    protected function getComponentOptions(): array
    {
        return [
            'inline' => $this->option('inline'),
        ];
    }

    protected function createView(): void
    {
        $module = $this->argument('module');
        $name = $this->argument('name');

        try {
            $this->generator->generateComponent('livewire-view', $name, [
                'module' => $module,
            ]);

            $this->components->info('View created successfully.');
        } catch (\Exception $e) {
            $this->components->warn('Failed to create view: '.$e->getMessage());
        }
    }

    protected function createTest(): void
    {
        $this->call('synapps:make-test', [
            'module' => $this->argument('module'),
            'name' => $this->argument('name').'Test',
            '--no-interaction' => true,
        ]);
    }
}
