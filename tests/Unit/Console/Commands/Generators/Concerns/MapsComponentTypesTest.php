<?php

declare(strict_types=1);

use VmEngine\Synapse\Console\Commands\Generators\Concerns\MapsComponentTypes;

beforeEach(function () {
    $this->subject = new class
    {
        use MapsComponentTypes;

        /** @return array<string, string> */
        public function map(): array
        {
            return $this->componentTypeMap();
        }

        /** @return array<string, string> */
        public function args(string $type, string $module, string $name): array
        {
            return $this->buildComponentArguments($type, $module, $name);
        }
    };
});

test('component type map includes every synapps:make-* command', function () {
    $map = $this->subject->map();

    expect($map)->toHaveKey('cast', 'synapps:make-cast')
        ->and($map)->toHaveKey('exception', 'synapps:make-exception')
        ->and($map)->toHaveKey('module', 'synapps:make-module')
        ->and($map)->toHaveKey('livewire', 'synapps:make-livewire')
        ->and($map)->toHaveKey('view', 'synapps:make-view');
});

test('component type map omits data when spatie/laravel-data is absent', function () {
    if (class_exists('Spatie\\LaravelData\\Data')) {
        $this->markTestSkipped('spatie/laravel-data is installed in this environment.');
    }

    expect($this->subject->map())->not->toHaveKey('data');
});

test('buildComponentArguments passes name only for the module type', function () {
    expect($this->subject->args('module', 'blog', 'ignored'))->toBe(['name' => 'blog']);
});

test('buildComponentArguments passes module and name for other types', function () {
    expect($this->subject->args('cast', 'blog', 'PriceCast'))
        ->toBe(['module' => 'blog', 'name' => 'PriceCast']);
});

test('buildComponentArguments omits name when empty', function () {
    expect($this->subject->args('cast', 'blog', ''))->toBe(['module' => 'blog']);
});
