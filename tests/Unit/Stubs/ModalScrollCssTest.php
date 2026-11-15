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

it('lays a drawer out as header, scrolling body and pinned footer', function () {
    $css = file_get_contents(__DIR__.'/../../../stubs/synapps/resources/css/synapse-components.css');

    preg_match('/^\.syn-drawer-panel \{(.*?)\}/ms', $css, $panel);
    preg_match('/^\.syn-drawer-body \{(.*?)\}/ms', $css, $body);
    preg_match('/^\.syn-drawer-footer \{(.*?)\}/ms', $css, $footer);

    expect($panel[1])->toContain('flex')->toContain('flex-col')->not->toContain('overflow-y-auto')->not->toContain(' p-4')
        ->and($body[1])->toContain('flex-1')->toContain('overflow-y-auto')
        ->and($footer[1])->toContain('shrink-0');
});

it('registers the named-drawer and adv-select indent Alpine code in the stub', function () {
    $js = file_get_contents(__DIR__.'/../../../stubs/synapps/resources/js/synapse-components.js');

    expect($js)->toContain("Alpine.data('synapseDrawer'")
        ->toContain('optionIndent(item)');
});

it('shows an image placeholder while loading and when broken, without beating positioning utilities', function () {
    $css = file_get_contents(__DIR__.'/../../../stubs/synapps/resources/css/synapse-components.css');

    // Behind loading images (skipping possibly-transparent SVG/PNG), and as ::after once broken.
    expect($css)->toMatch('/@layer base \{\s*img \{\s*position: relative;\s*\}\s*img:not\(\[src\$="\.svg" i\][^)]*\.no-img-placeholder\),\s*img::after \{/');
});
