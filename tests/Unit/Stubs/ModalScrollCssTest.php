<?php

declare(strict_types=1);

it('keeps a modal header and footer in view while its body scrolls', function () {
    $css = file_get_contents(__DIR__.'/../../../stubs/synapps/resources/css/synapse-components.css');

    preg_match('/^\.syn-modal-header \{(.*?)\}/ms', $css, $header);
    preg_match('/^\.syn-modal-footer \{(.*?)\}/ms', $css, $footer);

    // Sticky inside the scrolling panel, with a background so content scrolls underneath.
    expect($header[1])->toContain('sticky')->toContain('top-0')->toContain('bg-white')->toContain('dark:bg-gray-800')
        ->and($footer[1])->toContain('sticky')->toContain('bottom-0')->toContain('bg-white')->toContain('dark:bg-gray-800');
});

it('floats an adv-select dropdown out of a scrolling modal or drawer', function () {
    $js = file_get_contents(__DIR__.'/../../../stubs/synapps/resources/js/synapse-components.js');

    // An absolute dropdown inside an overflow-y-auto panel stretches it and is clipped by it.
    expect($js)->toContain("closest('.syn-modal-panel, .syn-drawer-panel')")
        ->toContain('floatDropdownInPanel()')
        ->toContain("position: 'fixed'");
});
