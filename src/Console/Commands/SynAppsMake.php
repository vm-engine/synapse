<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Console\Commands;

use Illuminate\Console\Command;
use VmEngine\Synapse\Console\Commands\Generators\Concerns\MapsComponentTypes;
use VmEngine\Synapse\Traits\HasTypedInput;

class SynAppsMake extends Command
{
    use HasTypedInput;
    use MapsComponentTypes;

    protected $signature = 'synapps:make
                            {object : The type of component to create}
                            {module : The module name}
                            {name? : The name of the component (optional only when object is module)}
                            {--path= : Custom base path for module}
                            {--mfc : Create multi-file component (livewire type only)}
                            {--sfc : Create single-file component (livewire type only)}
                            {--class : Create class-based component (livewire type only)}
                            {--inline : Create inline component (livewire/component types only)}
                            {--form : Create form object (livewire type only)}
                            {--test : Create Pest test alongside the component (livewire type only)}
                            {--discover : Run module discovery after generation}
                            ';

    protected $description = 'Create a component for a synapps/modules module. For type-specific options beyond the common set, call the underlying synapps:make-{type} command directly.';

    public function handle(): int
    {
        $map = $this->componentTypeMap();
        $type = $this->stringArgument('object');

        if (! isset($map[$type])) {
            $this->components->error("Invalid component type: {$type}");
            $this->components->info('Available types: '.implode(', ', array_keys($map)));

            return Command::FAILURE;
        }

        $module = $this->stringArgument('module');
        $name = $this->stringArgument('name');

        if ($type !== 'module' && $name === '') {
            $this->components->error('The name argument is required (it is optional only when object is module).');

            return Command::FAILURE;
        }

        $arguments = $this->buildComponentArguments($type, $module, $name);

        if ($this->option('path')) {
            $arguments['--path'] = $this->option('path');
        }

        if ($type === 'livewire') {
            foreach (['mfc', 'sfc', 'class', 'form', 'test'] as $flag) {
                if ($this->option($flag)) {
                    $arguments['--'.$flag] = true;
                }
            }
        }

        if (($type === 'livewire' || $type === 'component') && $this->option('inline')) {
            $arguments['--inline'] = true;
        }

        if ($this->option('discover')) {
            $arguments['--discover'] = true;
        }

        if ($this->option('no-interaction')) {
            $arguments['--no-interaction'] = true;
        }

        $command = $map[$type];
        $this->components->info("Running: {$command}");

        return $this->call($command, $arguments);
    }
}
