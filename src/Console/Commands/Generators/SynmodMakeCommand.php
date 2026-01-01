<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Console\Commands\Generators;

use Illuminate\Console\Command;

class SynmodMakeCommand extends Command
{
    protected $signature = 'synmod-make
                            {type? : The type of component to create}
                            {module? : The module name}
                            {name? : The name of the component}
                            {--path= : Custom base path for module}
                            ';

    protected $description = 'Unified command to create any component type for a module';

    /**
     * Map of component types to their make commands
     */
    protected array $componentTypes = [
        'cast' => 'synapps:make-cast',
        'channel' => 'synapps:make-channel',
        'command' => 'synapps:make-command',
        'component' => 'synapps:make-component',
        'config' => 'synapps:make-config',
        'controller' => 'synapps:make-controller',
        'enum' => 'synapps:make-enum',
        'event' => 'synapps:make-event',
        'factory' => 'synapps:make-factory',
        'interface' => 'synapps:make-interface',
        'job' => 'synapps:make-job',
        'job-middleware' => 'synapps:make-job-middleware',
        'listener' => 'synapps:make-listener',
        'livewire' => 'synapps:make-livewire',
        'mail' => 'synapps:make-mail',
        'middleware' => 'synapps:make-middleware',
        'migration' => 'synapps:make-migration',
        'model' => 'synapps:make-model',
        'notification' => 'synapps:make-notification',
        'observer' => 'synapps:make-observer',
        'policy' => 'synapps:make-policy',
        'provider' => 'synapps:make-provider',
        'request' => 'synapps:make-request',
        'resource' => 'synapps:make-resource',
        'rule' => 'synapps:make-rule',
        'scope' => 'synapps:make-scope',
        'seeder' => 'synapps:make-seeder',
        'test' => 'synapps:make-test',
        'trait' => 'synapps:make-trait',
        'view' => 'synapps:make-view',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $type = $this->argument('type');

        // If no type provided, show menu
        if (! $type) {
            $type = $this->choice(
                'What type of component do you want to create?',
                array_keys($this->componentTypes),
                0
            );
        }

        // Validate type
        if (! isset($this->componentTypes[$type])) {
            $this->components->error("Invalid component type: {$type}");
            $this->components->info('Available types: '.implode(', ', array_keys($this->componentTypes)));

            return Command::FAILURE;
        }

        // Get module and name
        $module = $this->argument('module');
        $name = $this->argument('name');

        // Build command arguments
        $arguments = [
            'module' => $module,
            'name' => $name,
        ];

        // Add path option if provided
        if ($this->option('path')) {
            $arguments['--path'] = $this->option('path');
        }

        // Call the specific make command
        $command = $this->componentTypes[$type];
        $this->components->info("Running: {$command}");

        return $this->call($command, $arguments);
    }

    /**
     * Get the console command arguments.
     */
    protected function getArguments(): array
    {
        return [
            ['type', null, 'The type of component to create'],
            ['module', null, 'The module name'],
            ['name', null, 'The name of the component'],
        ];
    }

    /**
     * Get the console command options.
     */
    protected function getOptions(): array
    {
        return [
            ['path', null, null, 'Custom base path for module'],
        ];
    }
}
