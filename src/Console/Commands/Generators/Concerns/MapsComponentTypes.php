<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Console\Commands\Generators\Concerns;

/**
 * Shared type => command map for the unified `synapps:make` and `synmod-make`
 * dispatcher commands, plus argument-building for the one type (`module`)
 * whose target command has a different argument shape than the rest
 * (`{name?}` only — there's no separate containing module to pass).
 */
trait MapsComponentTypes
{
    /**
     * Map of component types to their underlying synapps:make-* command name.
     *
     * @return array<string, string>
     */
    protected function componentTypeMap(): array
    {
        $map = [
            'cast' => 'synapps:make-cast',
            'channel' => 'synapps:make-channel',
            'class' => 'synapps:make-class',
            'command' => 'synapps:make-command',
            'component' => 'synapps:make-component',
            'config' => 'synapps:make-config',
            'controller' => 'synapps:make-controller',
            'data' => 'synapps:make-data',
            'enum' => 'synapps:make-enum',
            'event' => 'synapps:make-event',
            'exception' => 'synapps:make-exception',
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
            'module' => 'synapps:make-module',
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

        // MakeDataCommand is only registered when spatie/laravel-data is
        // installed (see SynapseServiceProvider::boot()) — keep the map
        // in sync so it never advertises a type with no registered command.
        if (! class_exists('Spatie\\LaravelData\\Data')) {
            unset($map['data']);
        }

        return $map;
    }

    /**
     * Build the argument array for calling the underlying synapps:make-* command.
     *
     * @return array<string, string>
     */
    protected function buildComponentArguments(string $type, string $module, string $name): array
    {
        if ($type === 'module') {
            return ['name' => $module];
        }

        $arguments = ['module' => $module];

        if ($name !== '') {
            $arguments['name'] = $name;
        }

        return $arguments;
    }
}
