<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Console\Commands\Generators;

use Illuminate\Support\Str;

class MakeLivewireCommand extends BaseGeneratorCommand
{
    protected $signature = 'synapps:make-livewire
                            {module : The module name}
                            {name? : The name of the Livewire component}
                            {--inline : Create inline component}
                            {--sfc : Create single-file component (Livewire v4 SFC)}
                            {--mfc : Create multi-file component (Livewire v4 MFC)}
                            {--form : Create form object}
                            {--test : Create Pest test}
                            {--path= : Custom base path for module}
                            ';

    protected $description = 'Create a new Livewire component for a module';

    protected string $componentType = 'livewire';

    public function handle(): int
    {
        // MFC and SFC use different generation flow
        if ($this->option('mfc')) {
            return $this->handleMfc();
        }

        if ($this->option('sfc')) {
            return $this->handleSfc();
        }

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

    protected function handleMfc(): int
    {
        $this->generator = app(\VmEngine\Synapse\Services\ModuleGenerator\ModuleGeneratorService::class);

        $module = $this->getModuleName();
        $name = $this->getComponentName();

        $this->setupGenerator($module);

        $modulePath = $this->resolveModulePath($module);
        $dirPrefix = $this->shouldUseEmoji() ? "\u{26A1}" : '';

        // Split name by / or \ to support nested paths (e.g., Forms/ContactForm)
        $segments = preg_split('/[\/\\\\]/', $name);
        $segments = array_map(fn (string $segment): string => Str::kebab($segment), $segments);

        // Last segment is the component name (gets emoji prefix on directory)
        $leafName = array_pop($segments);

        // Build the MFC directory path with optional subdirectories
        $mfcDir = $modulePath.'/resources/views/components';

        if (count($segments) > 0) {
            $mfcDir .= '/'.implode('/', $segments);
        }

        // MFC directory: resources/views/components/[subdir/][⚡]{component-name}/
        $mfcDir .= '/'.$dirPrefix.$leafName;

        if (! is_dir($mfcDir)) {
            mkdir($mfcDir, 0755, true);
        }

        // Generate PHP file from MFC stub
        $stubRenderer = $this->generator->stubRenderer;
        $variables = [
            'class' => Str::studly(str_replace('-', '_', $leafName)),
            'moduleSlug' => Str::slug($module),
        ];

        $phpContent = $stubRenderer->render('livewire/component.mfc', $variables);
        $bladeContent = $stubRenderer->render('livewire/component.mfc.blade', $variables);

        file_put_contents($mfcDir.'/'.$leafName.'.php', $phpContent);
        file_put_contents($mfcDir.'/'.$leafName.'.blade.php', $bladeContent);

        $this->components->info('MFC Livewire component created successfully.');
        $this->components->info("Location: {$mfcDir}/");

        if ($this->option('test')) {
            $this->createTest();
        }

        return 0;
    }

    protected function handleSfc(): int
    {
        $this->generator = app(\VmEngine\Synapse\Services\ModuleGenerator\ModuleGeneratorService::class);

        $module = $this->getModuleName();
        $name = $this->getComponentName();

        $this->setupGenerator($module);

        $modulePath = $this->resolveModulePath($module);
        $kebabName = Str::kebab($name);
        $filePrefix = $this->shouldUseEmoji() ? "\u{26A1}" : '';

        // SFC file: resources/views/components/[⚡]{component-name}.blade.php
        $componentsDir = $modulePath.'/resources/views/components';

        if (! is_dir($componentsDir)) {
            mkdir($componentsDir, 0755, true);
        }

        $stubRenderer = $this->generator->stubRenderer;
        $variables = [
            'class' => Str::studly($name),
            'moduleSlug' => Str::slug($module),
        ];

        $sfcContent = $stubRenderer->render('livewire/component.sfc', $variables);
        $sfcPath = $componentsDir.'/'.$filePrefix.$kebabName.'.blade.php';

        file_put_contents($sfcPath, $sfcContent);

        $this->components->info('SFC Livewire component created successfully.');
        $this->components->info("Location: {$sfcPath}");

        if ($this->option('test')) {
            $this->createTest();
        }

        return 0;
    }

    protected function shouldUseEmoji(): bool
    {
        return (bool) config('livewire.make_command.emoji', true);
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
