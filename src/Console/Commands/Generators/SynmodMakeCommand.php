<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Console\Commands\Generators;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use VmEngine\Synapse\Traits\HasTypedInput;

class SynmodMakeCommand extends Command
{
    use HasTypedInput;

    protected $signature = 'synmod-make
                            {type? : The type of component to create}
                            {module? : The module name}
                            {name? : The name of the component}
                            {--path= : Custom base path for module}
                            {--mfc : Create multi-file component (livewire type only)}
                            {--sfc : Create single-file component (livewire type only)}
                            {--inline : Create inline component (livewire type only)}
                            {--test : Create Pest test alongside the component (livewire type only)}
                            ';

    protected $description = 'Unified command to create any component type for a module';

    /**
     * Map of component types to their make commands
     *
     * @var array<string, string>
     */
    protected array $componentTypes = [
        'cast' => 'synapps:make-cast',
        'data' => 'synapps:make-data',
        'channel' => 'synapps:make-channel',
        'class' => 'synapps:make-class',
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
        $type = $this->stringArgument('type');

        // If no type provided, show menu
        if ($type === '') {
            // choice() is typed array|string because of its $multiple flag,
            // which we never pass — this call always returns a single string.
            $choice = $this->choice(
                'What type of component do you want to create?',
                array_keys($this->componentTypes),
                0
            );
            $type = is_array($choice) ? (string) ($choice[0] ?? '') : $choice;
        }

        // Validate type
        if (! isset($this->componentTypes[$type])) {
            $this->components->error("Invalid component type: {$type}");
            $this->components->info('Available types: '.implode(', ', array_keys($this->componentTypes)));

            return Command::FAILURE;
        }

        // Get module and name
        $module = $this->stringArgument('module');
        $name = $this->stringArgument('name');

        // Build command arguments
        $arguments = [
            'module' => $module,
            'name' => $name,
        ];

        // Add path option if provided
        if ($this->option('path')) {
            $arguments['--path'] = $this->option('path');
        }

        // Pass livewire-specific flags through when the type is livewire
        if ($type === 'livewire') {
            if ($this->option('mfc')) {
                $arguments['--mfc'] = true;
            }

            if ($this->option('sfc')) {
                $arguments['--sfc'] = true;
            }

            if ($this->option('inline')) {
                $arguments['--inline'] = true;
            }

            if ($this->option('test')) {
                $arguments['--test'] = true;
            }
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
            ['type', InputArgument::REQUIRED, 'The type of component to create'],
            ['module', InputArgument::REQUIRED, 'The module name'],
            ['name', InputArgument::OPTIONAL, 'The name of the component'],
        ];
    }

    /**
     * Get the console command options.
     */
    protected function getOptions(): array
    {
        return [
            new InputOption('path', 'p', InputOption::VALUE_OPTIONAL, 'Custom base path for module'),
            new InputOption('mfc', null, InputOption::VALUE_NONE, 'Create multi-file component (livewire type only)'),
            new InputOption('sfc', null, InputOption::VALUE_NONE, 'Create single-file component (livewire type only)'),
            new InputOption('inline', null, InputOption::VALUE_NONE, 'Create inline component (livewire type only)'),
            new InputOption('test', null, InputOption::VALUE_NONE, 'Create Pest test alongside the component (livewire type only)'),
        ];
    }
}
