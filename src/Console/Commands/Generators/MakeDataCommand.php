<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Console\Commands\Generators;

use Illuminate\Console\Command;
use Spatie\LaravelData\Data;

class MakeDataCommand extends BaseGeneratorCommand
{
    protected $signature = 'synapps:make-data
                            {module : The module name}
                            {name? : The name of the data class}
                            {--path= : Custom base path for module}
                            ';

    protected $description = 'Create a new Spatie Laravel Data class for a module';

    protected string $componentType = 'data';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! class_exists(Data::class)) {
            $this->components->error('spatie/laravel-data is not installed.');
            $this->components->info('Install it with: composer require spatie/laravel-data');

            return Command::FAILURE;
        }

        return parent::handle();
    }
}
