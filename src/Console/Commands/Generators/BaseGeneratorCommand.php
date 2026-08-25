<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Console\Commands\Generators;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;
use VmEngine\Synapse\Services\ModuleGenerator\ModuleGeneratorService;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;

abstract class BaseGeneratorCommand extends Command
{
    protected ModuleGeneratorService $generator;

    protected function configure(): void
    {
        parent::configure();
        $this->addOption('discover', null, InputOption::VALUE_NONE, 'Run module discovery after generation');
    }

    /**
     * Component type (model, controller, migration, etc.)
     */
    protected string $componentType;

    /**
     * File extension (typically .php)
     */
    protected string $fileExtension = '.php';

    /**
     * Execute command
     */
    public function handle(): int
    {
        $this->generator = app(ModuleGeneratorService::class);

        try {
            // Get module name/path
            $module = $this->getModuleName();

            // Get component name
            $name = $this->getComponentName();

            // Setup generator with module context
            $this->setupGenerator($module);

            // Get additional options
            $options = $this->getComponentOptions();
            $options['module'] = $module;

            // Generate component
            $path = $this->generator->generateComponent(
                $this->componentType,
                $name,
                $options
            );

            $this->components->info(ucfirst($this->componentType).' created successfully.');
            $this->components->info("Location: {$path}");

            // Run discovery if needed
            if ($this->generator->shouldRunDiscovery($this->componentType)) {
                if ($this->shouldRunDiscovery()) {
                    $this->components->info('Running module discovery...');
                    $this->generator->runDiscovery();
                    $this->components->success('Discovery complete.');
                }
            }

            return 0;
        } catch (\Exception $e) {
            $this->components->error($e->getMessage());

            return 1;
        }
    }

    /**
     * Get module name (interactive or from argument)
     */
    protected function getModuleName(): string
    {
        if ($this->hasArgument('module') && $this->argument('module')) {
            return $this->argument('module');
        }

        if ($this->option('no-interaction')) {
            throw new \RuntimeException('Module name is required when running without interaction.');
        }

        return text(
            label: 'Module name',
            required: true,
            hint: 'The name of the module (e.g., blog, todos)'
        );
    }

    /**
     * Get component name (interactive or from argument)
     */
    protected function getComponentName(): string
    {
        if ($this->hasArgument('name') && $this->argument('name')) {
            return $this->argument('name');
        }

        if ($this->option('no-interaction')) {
            throw new \RuntimeException('Component name is required when running without interaction.');
        }

        return text(
            label: ucfirst($this->componentType).' name',
            required: true,
            hint: 'The name of the '.$this->componentType
        );
    }

    /**
     * Get component-specific options
     * Override in subclasses to add custom options
     */
    /**
     * @return array<string, mixed>
     */
    protected function getComponentOptions(): array
    {
        return [];
    }

    /**
     * Setup generator with module configuration
     */
    protected function setupGenerator(string $module): void
    {
        $modulePath = $this->resolveModulePath($module);
        $this->generator->setBasePath($modulePath);
    }

    /**
     * Resolve module base path
     */
    protected function resolveModulePath(string $module): string
    {
        // If custom path provided via option
        if ($this->hasOption('path') && $this->option('path')) {
            return $this->option('path');
        }

        // Default to synapps/modules/{module} for directory modules
        $moduleName = Str::snake($module);

        return base_path('synapps/modules/'.$moduleName);
    }

    /**
     * Check if discovery should run
     */
    protected function shouldRunDiscovery(): bool
    {
        return (bool) $this->option('discover');
    }

    /**
     * Get option value with fallback to prompt
     */
    protected function getOptionOrPrompt(
        string $option,
        string $label,
        mixed $default = null,
        ?string $hint = null,
        bool $required = false
    ): mixed {
        // If option is provided, use it
        if ($this->hasOption($option) && $this->option($option) !== null) {
            return $this->option($option);
        }

        // If running without interaction, return default
        if ($this->option('no-interaction')) {
            return $default;
        }

        // Prompt user
        return text(
            label: $label,
            default: $default,
            hint: $hint,
            required: $required
        );
    }

    /**
     * Get boolean option with fallback to prompt
     */
    protected function getBooleanOption(
        string $option,
        string $label,
        bool $default = false
    ): bool {
        // If option is provided, use it
        if ($this->hasOption($option) && $this->option($option)) {
            return true;
        }

        // If running without interaction, return default
        if ($this->option('no-interaction')) {
            return $default;
        }

        // Prompt user
        return confirm(
            label: $label,
            default: $default
        );
    }
}
