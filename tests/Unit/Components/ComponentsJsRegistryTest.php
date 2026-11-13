<?php

declare(strict_types=1);

describe('synapse-components.js stub', function () {
    it('registers the Alpine.data each JS-backed component needs', function (string $name) {
        $js = file_get_contents(__DIR__.'/../../../stubs/synapps/resources/js/synapse-components.js');

        expect($js)->toContain("Alpine.data('{$name}'");
    })->with(['synapseColorPicker', 'synapseSplitPane', 'synapseCopyButton', 'synapseCurrency', 'synapseLoader']);
});

it('syncs a live raw currency input with one debounced $commit, not a request per keystroke', function () {
    $js = (string) file_get_contents(__DIR__.'/../../../stubs/synapps/resources/js/synapse-components.js');
    preg_match("#Alpine\\.data\\('synapseCurrency'.*?\\n\\}\\)\\);#s", $js, $component);

    expect($component[0] ?? '')->toContain('$commit()')->toContain('setTimeout');
});

// livewire:navigating fires after the page fetch, right before the swap — too late to cover the
// wait. livewire:navigate fires on the click; it is cancelable, so a cancelled one must reset.
it('shows the loader from the start of a wire:navigate, not just before the swap', function () {
    $js = (string) file_get_contents(__DIR__.'/../../../stubs/synapps/resources/js/synapse-components.js');
    preg_match("#Alpine\\.data\\('synapseLoader'.*?\\n\\}\\)\\);#s", $js, $component);

    expect($component[0] ?? '')->toContain("'livewire:navigate'")->toContain('defaultPrevented')
        ->not->toContain("'livewire:navigating'");
});
