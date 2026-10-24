<?php

declare(strict_types=1);

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/** Runs the raw-currency parse/format helpers from the JS stub in Node, so money conversion is checked, not just present. */
it('converts between the shown text and the plain wire value', function () {
    $node = (new ExecutableFinder)->find('node');
    if ($node === null) {
        $this->markTestSkipped('node is not installed');
    }

    $js = (string) file_get_contents(__DIR__.'/../../../stubs/synapps/resources/js/synapse-components.js');
    preg_match('#// currency-helpers:start(.*)// currency-helpers:end#s', $js, $match);

    $script = $match[1].<<<'JS'
        const cases = {
            parse: [['1.250.000,50', '1250000.50'], ['1.250.000', '1250000'], ['', ''], ['12,', '12'], ['-1.000,5', '-1000.5'], ['Rp 10.000', '10000']],
            format: [['1250000.50', 2, '1.250.000,5'], ['1250000.00', 2, '1.250.000'], ['50000', 0, '50.000'], ['0.75', 2, '0,75'], ['', 2, ''], [null, 2, ''], ['-1000.5', 2, '-1.000,5'], ['999', 2, '999']],
        };
        const failures = [];
        cases.parse.forEach(([input, want]) => { const got = synapseCurrencyParse(input); if (got !== want) failures.push(`parse(${input}) = ${got}, want ${want}`); });
        cases.format.forEach(([input, decimals, want]) => { const got = synapseCurrencyFormat(input, decimals); if (got !== want) failures.push(`format(${input}, ${decimals}) = ${got}, want ${want}`); });
        console.log(failures.join('\n'));
        JS;

    $process = new Process([$node, '-e', $script]);
    $process->run();

    expect($process->getErrorOutput())->toBe('')->and(trim($process->getOutput()))->toBe('');
});
