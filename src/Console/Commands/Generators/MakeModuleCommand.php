<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Console\Commands\Generators;

use VmEngine\Synapse\Services\ModuleGenerator\ModuleGeneratorService;

use function Laravel\Prompts\text;

class MakeModuleCommand extends BaseGeneratorCommand
{
    protected $signature = 'synapps:make-module
                            {name? : The name of the module}
                            {--path= : Custom base path for module}
                            {--type=directory : Module type (directory|package)}
                            {--author= : Module author name}
                            {--email= : Module author email}
                            {--description= : Module description}
                            {--url-prefix= : URL prefix for routes}
                            {--git-repo= : Git repository URL}
                            {--with-model : Create example model}
                            {--with-controller : Create example controller}
                            {--with-livewire : Create example Livewire component}';

    protected $description = 'Create a new Synapse module with complete structure';

    protected string $componentType = 'module';

    /**
     * Execute command
     */
    public function handle(): int
    {
        $this->generator = app(ModuleGeneratorService::class);

        try {
            // Get module name
            $name = $this->getModuleName();

            // Get module options
            $options = $this->getModuleOptions();

            // Setup generator
            $this->setupGeneratorForNewModule($name);

            // Generate module
            $this->components->info("Creating module: {$name}");
            $this->generator->generateModule($name, $options);

            $modulePath = $this->generator->isPackageModule()
                ? $this->stringOption('path')
                : base_path('synapps/modules/'.strtolower($name));

            $this->components->success('Module created successfully!');
            $this->components->info("Location: {$modulePath}");
            $this->newLine();

            // Show next steps
            $this->showNextSteps($name);

            // Run discovery
            if ($this->shouldRunDiscovery()) {
                $this->components->info('Running module discovery...');
                $this->generator->runDiscovery();
                $this->components->success('Discovery complete.');
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
        if ($this->argument('name')) {
            return $this->stringArgument('name');
        }

        if ($this->option('no-interaction')) {
            throw new \RuntimeException('Module name is required when running without interaction.');
        }

        return text(
            label: 'Module name',
            required: true,
            hint: 'The name of the module (e.g., blog, todos, shop)'
        );
    }

    /**
     * Get module configuration options
     *
     * @return array<string, mixed>
     */
    protected function getModuleOptions(): array
    {
        return [
            'author' => $this->getOptionOrPrompt(
                'author',
                'Author name',
                '',
                'The module author'
            ),
            'email' => $this->getOptionOrPrompt(
                'email',
                'Author email',
                '',
                'Author email address'
            ),
            'description' => $this->getOptionOrPrompt(
                'description',
                'Module description',
                '',
                'Brief description of the module'
            ),
            'url_prefix' => $this->getOptionOrPrompt(
                'url-prefix',
                'URL prefix',
                '',
                'URL prefix for module routes (leave empty for root)'
            ),
            'git_repo' => $this->getOptionOrPrompt(
                'git-repo',
                'Git repository URL',
                '',
                'Git repository URL (optional)'
            ),
            'with_model' => $this->option('with-model') ?? false,
            'with_controller' => $this->option('with-controller') ?? false,
            'with_livewire' => $this->option('with-livewire') ?? false,
        ];
    }

    /**
     * Setup generator for new module
     */
    protected function setupGeneratorForNewModule(string $name): void
    {
        $type = $this->option('type');
        $customPath = $this->option('path');

        if ($customPath) {
            // Custom path provided
            $this->generator->setBasePath($this->stringOption('path'));
        } elseif ($type === 'package') {
            // Package module - needs vendor/org/synmod-name structure
            $vendorPath = base_path('vendor/vm-engine/synmod-'.strtolower($name));
            $this->generator->setBasePath($vendorPath);
        } else {
            // Directory module - default synapps/modules
            $directoryPath = base_path('synapps/modules');
            $this->generator->setBasePath($directoryPath);
        }
    }

    /**
     * Show next steps to user
     */
    protected function showNextSteps(string $name): void
    {
        $this->components->info('Next steps:');
        $this->line('  1. Update module.json with your configuration');
        $this->line('  2. Add routes in routes/web.php or routes/web.backend.php');
        $this->line('  3. Create models, controllers, or Livewire components:');
        $this->line("     php artisan synapps:make-model {$name} ModelName");
        $this->line("     php artisan synapps:make-controller {$name} ControllerName");
        $this->line("     php artisan synapps:make-livewire {$name} ComponentName");
    }

    /**
     * Override to get module name differently for module creation
     */
    protected function getComponentName(): string
    {
        return $this->getModuleName();
    }
}
