<?php

declare(strict_types=1);

describe('synapse-components.js stub', function () {
    it('registers the Alpine.data used by the 3.1 components', function (string $name) {
        $js = file_get_contents(__DIR__.'/../../../stubs/synapps/resources/js/synapse-components.js');

        expect($js)->toContain("Alpine.data('{$name}'");
    })->with(['synapseColorPicker', 'synapseSplitPane']);
});
