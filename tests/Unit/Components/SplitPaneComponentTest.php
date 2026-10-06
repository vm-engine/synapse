<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use VmEngine\Synapse\Components\SplitPane;

describe('SplitPane Component', function () {
    beforeEach(function () {
        view()->addNamespace('synapse', __DIR__.'/../../../resources/views');
        Blade::component(SplitPane::class, 'synapse-split-pane');
    });

    it('keeps a valid aside width and falls back to 20rem otherwise', function () {
        expect((new SplitPane(asideWidth: '300px'))->asideWidth)->toBe('300px')
            ->and((new SplitPane(asideWidth: '25%'))->asideWidth)->toBe('25%')
            ->and((new SplitPane(asideWidth: '20rem;color:red'))->asideWidth)->toBe('20rem');
    });

    it('only keeps a safe storage key', function () {
        expect((new SplitPane(storageKey: 'example-nodes'))->storageKey)->toBe('example-nodes')
            ->and((new SplitPane(storageKey: "x'y"))->storageKey)->toBeNull();
    });

    it('renders aside and main slots with a handle when resizable', function () {
        $html = Blade::render(<<<'BLADE'
            <x-synapse-split-pane resizable storage-key="nodes" aside-width="18rem">
                <x-slot:aside>TREE</x-slot:aside>
                EDITOR
            </x-synapse-split-pane>
            BLADE);

        expect($html)->toContain('synapseSplitPane(&quot;nodes&quot;, &quot;18rem&quot;)')
            ->toContain('--syn-split-aside: 18rem')
            ->toContain('syn-split-pane-resizable')
            ->toContain('syn-split-pane-handle')
            ->toContain('TREE')
            ->toContain('EDITOR');
    });

    it('omits the handle and storage key when not resizable', function () {
        $html = Blade::render(<<<'BLADE'
            <x-synapse-split-pane storage-key="nodes">
                <x-slot:aside>TREE</x-slot:aside>
                EDITOR
            </x-synapse-split-pane>
            BLADE);

        expect($html)->toContain('synapseSplitPane(null, &quot;20rem&quot;)')
            ->not->toContain('syn-split-pane-handle')
            ->not->toContain('syn-split-pane-resizable');
    });
});
