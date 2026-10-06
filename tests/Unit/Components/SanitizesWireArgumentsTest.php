<?php

declare(strict_types=1);

use VmEngine\Synapse\Components\Concerns\SanitizesWireArguments;

function wireArgumentsSanitizer(): object
{
    return new class
    {
        use SanitizesWireArguments;

        public function method(?string $method): ?string
        {
            return $this->wireMethod($method);
        }

        public function key(int|string|null $key): ?string
        {
            return $this->safeKey($key);
        }
    };
}

describe('SanitizesWireArguments', function () {
    it('accepts plain PHP method names', function () {
        expect(wireArgumentsSanitizer()->method('moveItem'))->toBe('moveItem')
            ->and(wireArgumentsSanitizer()->method('_go2'))->toBe('_go2');
    });

    it('rejects anything that is not a bare method name', function (?string $method) {
        expect(wireArgumentsSanitizer()->method($method))->toBeNull();
    })->with([null, '', 'go()', 'go(); alert(1)', '2fast', 'a-b', '$refresh']);

    it('accepts slug-like keys and integer keys', function () {
        expect(wireArgumentsSanitizer()->key(42))->toBe('42')
            ->and(wireArgumentsSanitizer()->key('in-review_2'))->toBe('in-review_2');
    });

    it('rejects keys that could break out of a quoted argument', function (int|string|null $key) {
        expect(wireArgumentsSanitizer()->key($key))->toBeNull();
    })->with([null, '', "a'b", 'a b', '<x>', 'a"b']);
});
