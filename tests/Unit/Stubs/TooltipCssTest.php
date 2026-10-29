<?php

declare(strict_types=1);

it('keeps tooltip text in the accessibility tree and shows it on keyboard focus', function () {
    $css = file_get_contents(__DIR__.'/../../../stubs/themes/default/css/theme-base.css');

    preg_match('/^\.tooltip \{(.*?)\}/ms', $css, $tooltip);
    preg_match('/^\.has-tooltip > \.tooltip \{(.*?)\}/ms', $css, $shown);

    expect($tooltip[1])->not->toContain('invisible')
        ->toContain('opacity-0')
        ->toContain('pointer-events-none')
        ->and($shown[1])->toContain('group-hover:opacity-100')
        ->toContain('group-focus-visible:opacity-100');
});

it('gives icon buttons a 44px square hit target after the tooltip rules', function () {
    $css = file_get_contents(__DIR__.'/../../../stubs/themes/default/css/theme-base.css');

    expect($css)->toContain('.btn-icon,')
        ->toContain('size-11')
        // must come after .has-tooltip, whose inline-block would otherwise win
        ->and(strpos($css, '.has-tooltip {'))->toBeLessThan(strpos($css, 'size-11'));
});

it('keeps row actions on one line', function () {
    $css = file_get_contents(__DIR__.'/../../../stubs/synapps/resources/css/synapse-components.css');

    expect($css)->toContain('.datatable td > div:not(.flex):has(> .btn-icon)');
});

it('uses motion tokens for button transitions', function () {
    $css = file_get_contents(__DIR__.'/../../../stubs/themes/default/css/theme-base.css');

    preg_match('/^\.btn-icon \{(.*?)\}/ms', $css, $m);

    expect($m[1])->not->toContain('duration-500')->toContain('duration-150');
});
