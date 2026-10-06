<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Console\Commands\Generators;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use VmEngine\Synapse\Console\Commands\Generators\Concerns\MapsComponentTypes;
use VmEngine\Synapse\Traits\HasTypedInput;

class SynmodMakeCommand extends Command
{
    use HasTypedInput;
    use MapsComponentTypes;

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
     * Execute the console command.
     */
    public function handle(): int
    {
        $map = $this->componentTypeMap();
        $type = $this->stringArgument('type');

        // If no type provided, show menu
        if ($type === '') {
            // choice() is typed array|string because of its $multiple flag,
            // which we never pass — this call always returns a single string.
            $choice = $this->choice(
                'What type of component do you want to create?',
                array_keys($map),
                0
            );
            $type = is_array($choice) ? (string) ($choice[0] ?? '') : $choice;
        }

        // Validate type
        if (! isset($map[$type])) {
            $this->components->error("Invalid component type: {$type}");
            $this->components->info('Available types: '.implode(', ', array_keys($map)));

            return Command::FAILURE;
        }

        // Get module and name
        $module = $this->stringArgument('module');
        $name = $this->stringArgument('name');

        // Build command arguments
        $arguments = $this->buildComponentArguments($type, $module, $name);

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

        if ($this->option('no-interaction')) {
            $arguments['--no-interaction'] = true;
        }

        // Call the specific make command
        $command = $map[$type];
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
