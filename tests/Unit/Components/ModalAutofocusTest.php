<?php

declare(strict_types=1);

it('focuses the first [autofocus] field when a modal opens', function () {
    // HTML autofocus only fires on page load, so modals that open later need
    // synapseModal to focus it; views can't do it inline under CSP Alpine.
    $js = (string) file_get_contents(__DIR__.'/../../../stubs/synapps/resources/js/synapse-components.js');

    // open() delegates to focusFirst(), which prefers [autofocus] over the first focusable control.
    expect($js)->toMatch("/Alpine\\.data\\('synapseModal'[\\s\\S]*?open\\(\\) \\{[\\s\\S]*?this\\.focusFirst\\(\\)/")
        ->toMatch("/focusFirst\\(\\) \\{[\\s\\S]*?querySelector\\('\\[autofocus\\]'\\)/");
});

it('focuses the first focusable element when no [autofocus] is present', function () {
    $js = (string) file_get_contents(__DIR__.'/../../../stubs/synapps/resources/js/synapse-components.js');

    expect($js)->toContain('SYNAPSE_FOCUSABLE')
        ->toMatch("/Alpine\\.data\\('synapseModal'[\\s\\S]*?focusFirst\\(\\)/");
});

it('restores focus to the trigger when the modal closes', function () {
    $js = (string) file_get_contents(__DIR__.'/../../../stubs/synapps/resources/js/synapse-components.js');

    expect($js)->toMatch("/Alpine\\.data\\('synapseModal'[\\s\\S]*?this\\.trigger[\\s\\S]*?close\\(\\)/")
        ->toMatch('/trapTab\\(event\\)/');
});

it('wires the trap to the dialog panel', function () {
    $blade = (string) file_get_contents(__DIR__.'/../../../resources/views/components/modal.blade.php');

    expect($blade)->toContain('@keydown.tab.prevent="trapTab($event)"')
        ->toContain('x-ref="panel"');
});
