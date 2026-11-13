<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use VmEngine\Synapse\Components\RadioButtons;

describe('RadioButtons Component', function () {
    beforeEach(function () {
        view()->addNamespace('synapse', __DIR__.'/../../../resources/views');
        Blade::component(RadioButtons::class, 'synapse-radio-buttons');
    });

    // The model used to be interpolated bare — `{ selected: }` for an empty value is a parse error,
    // and any non-numeric value became an undefined variable (or extra JavaScript) in the expression.
    it('passes the selected value to Alpine as a JSON string literal', function (string $model, string $expected) {
        $html = Blade::render('<x-synapse-radio-buttons name="radio" :options="[1, 2]" :model="$model" />', ['model' => $model]);

        expect($html)->toContain('x-data="{ selected: '.e($expected).' }"');
    })->with([
        'empty' => ['', '""'],
        'numeric' => ['2', '"2"'],
        'text' => ['draft', '"draft"'],
        'injection' => ['1, x: alert(1)', '"1, x: alert(1)"'],
    ]);
});
