<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use VmEngine\Synapse\Components\Toastr;

describe('Toastr Component', function () {
    it('passes session flash to x-data as a raw JSON literal, not JSON.parse(), for CSP-safe Alpine', function () {
        // See AdvSelectComponentTest for why the tag is registered by hand.
        view()->addNamespace('synapse', __DIR__.'/../../../resources/views');
        Blade::component(Toastr::class, 'synapse-toastr');

        // Js::from() only emits JSON.parse(...) for non-empty data, so the bug hid
        // until a page was reached via a redirect carrying a flash message.
        session()->flash('success', 'Saved "Dummy"');

        $html = Blade::render('<x-synapse-toastr />');

        expect($html)->toContain('x-data="toastrRoot([')
            ->and($html)->not->toContain('toastrRoot(JSON.parse')
            ->and($html)->toContain('Saved \&quot;Dummy\&quot;');
    });
});
