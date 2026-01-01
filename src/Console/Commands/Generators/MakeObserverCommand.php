<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Console\Commands\Generators;

class MakeObserverCommand extends BaseGeneratorCommand
{
    protected $signature = 'synapps:make-observer
                            {module : The module name}
                            {name? : The name of the observer}
                            {--model= : The model that the observer applies to}
                            {--path= : Custom base path for module}
                            ';

    protected $description = 'Create a new observer class for a module';

    protected string $componentType = 'observer';

    protected function getComponentOptions(): array
    {
        $model = $this->option('model');

        if ($model) {
            $modelClass = class_basename($model);
            $modelVariable = lcfirst($modelClass);

            return [
                'model' => $modelClass,
                'modelVariable' => $modelVariable,
                'modelImport' => $model !== $modelClass ? "use {$model};" : '',
            ];
        }

        return [
            'model' => 'Model',
            'modelVariable' => 'model',
            'modelImport' => 'use Illuminate\Database\Eloquent\Model;',
        ];
    }
}
