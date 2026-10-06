<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use VmEngine\Synapse\Components\Steps;

describe('Steps Component', function () {
    beforeEach(function () {
        view()->addNamespace('synapse', __DIR__.'/../../../resources/views');
        Blade::component(Steps::class, 'synapse-steps');
    });

    it('clamps current into the valid range', function () {
        expect((new Steps(steps: ['A', 'B', 'C'], current: 9))->current)->toBe(3)
            ->and((new Steps(steps: ['A', 'B', 'C'], current: 0))->current)->toBe(1)
            ->and((new Steps(steps: [], current: 4))->current)->toBe(1);
    });

    it('derives complete, current and upcoming states', function () {
        $items = (new Steps(steps: ['Details', 'Media', 'Review'], current: 2))->items();

        expect(array_column($items, 'state'))->toBe(['complete', 'current', 'upcoming'])
            ->and(array_column($items, 'number'))->toBe([1, 2, 3])
            ->and(array_column($items, 'clickable'))->toBe([false, false, false]);
    });

    it('makes only completed steps clickable when a go method is set', function () {
        $items = (new Steps(steps: ['A', 'B', 'C'], current: 3, goMethod: 'goToStep'))->items();

        expect(array_column($items, 'clickable'))->toBe([true, true, false]);
    });

    it('drops an unsafe go method', function () {
        expect((new Steps(steps: ['A'], goMethod: 'go(); alert(1)'))->goMethod)->toBeNull();
    });

    it('renders wire:click only on completed steps and marks the current step', function () {
        $html = Blade::render('<x-synapse-steps :steps="[\'A\', \'B\', \'C\']" :current="3" go-method="goToStep" />');

        expect($html)->toContain('wire:click="goToStep(1)"')
            ->toContain('wire:click="goToStep(2)"')
            ->not->toContain('goToStep(3)')
            ->toContain('aria-current="step"')
            ->toContain('syn-step-complete')
            ->toContain('syn-step-current');
    });

    it('escapes step labels', function () {
        $html = Blade::render('<x-synapse-steps :steps="[\'<script>x</script>\']" />');

        expect($html)->toContain('&lt;script&gt;')
            ->not->toContain('<script>x</script>');
    });
});
