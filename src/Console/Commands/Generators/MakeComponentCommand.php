<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Console\Commands\Generators;

class MakeComponentCommand extends BaseGeneratorCommand
{
    protected $signature = 'synapps:make-component
                            {module : The module name}
                            {name? : The name of the component}
                            {--inline : Create inline component without view}
                            {--path= : Custom base path for module}
                            ';

    protected $description = 'Create a new view component class for a module';

    protected string $componentType = 'component';

    protected string $componentName = '';

    public function handle(): int
    {
        // Store component name before parent handle
        $this->componentName = $this->hasArgument('name') && $this->argument('name')
            ? $this->argument('name')
            : '';

        $result = parent::handle();

        // If name was from prompt, get it from argument now
        if (empty($this->componentName)) {
            $this->componentName = $this->argument('name') ?? '';
        }

        // Create view file unless inline
        if ($result === 0 && ! $this->option('inline')) {
            $this->createView();
        }

        return $result;
    }

    protected function getComponentOptions(): array
    {
        // Name might not be set yet if from prompt, so use generic view path
        $name = $this->componentName ?: ($this->hasArgument('name') ? $this->argument('name') : 'component');
        $viewName = strtolower(str_replace(['\\', '/'], '.', $name));

        return [
            'view' => "components.{$viewName}",
        ];
    }

    protected function createView(): void
    {
        $module = $this->argument('module');
        $name = $this->componentName ?: $this->argument('name');

        $viewName = strtolower(str_replace(['\\', '/'], '.', $name));
        $className = class_basename($name);

        try {
            $this->generator->generateComponent(
                'component-view',
                "components/{$viewName}",
                [
                    'class' => $className,
                    'module' => $module,
                ]
            );

            $this->components->info("Component view created successfully: resources/views/components/{$viewName}.blade.php");
        } catch (\Exception $e) {
            $this->components->error("Failed to create component view: {$e->getMessage()}");
        }
    }
}
