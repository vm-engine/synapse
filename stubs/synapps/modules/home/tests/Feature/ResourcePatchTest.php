<?php

it('check if resource files is patched', function () {
    // synapps.css/js load through the per-layout split: base.css/js -> synapps-base.css/js -> synapps.css/js
    expect(file_get_contents(resource_path('css/base.css')))
        ->toContain('resources/css/synapps-base.css');
    expect(file_get_contents(synapps_path('resources/css/synapps-base.css')))
        ->toContain('synapps.css');

    expect(file_get_contents(resource_path('js/base.js')))
        ->toContain('resources/js/synapps-base.js');
    expect(file_get_contents(synapps_path('resources/js/synapps-base.js')))
        ->toContain('synapps.js');
});
