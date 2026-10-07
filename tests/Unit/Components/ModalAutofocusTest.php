<?php

declare(strict_types=1);

it('focuses the first [autofocus] field when a modal opens', function () {
    // HTML autofocus only fires on page load, so modals that open later need
    // synapseModal to focus it; views can't do it inline under CSP Alpine.
    $js = (string) file_get_contents(__DIR__.'/../../../stubs/synapps/resources/js/synapse-components.js');

    expect($js)->toMatch("/Alpine\\.data\\('synapseModal'[\\s\\S]*?open\\(\\) \\{[\\s\\S]*?querySelector\\('\\[autofocus\\]'\\)\\?\\.focus\\(\\)/");
});
