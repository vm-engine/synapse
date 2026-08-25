<?php

declare(strict_types=1);

describe('array_is_list()', function () {
    it('returns true for empty array', function () {
        expect(array_is_list([]))->toBeTrue();
    });

    it('returns true for sequential numeric keys', function () {
        expect(array_is_list([1, 2, 3]))->toBeTrue();
        expect(array_is_list(['a', 'b', 'c']))->toBeTrue();
    });

    it('returns false for associative arrays', function () {
        expect(array_is_list(['a' => 1, 'b' => 2]))->toBeFalse();
    });

    it('returns false for non-sequential numeric keys', function () {
        expect(array_is_list([0 => 'a', 2 => 'b']))->toBeFalse();
        expect(array_is_list([1 => 'a', 2 => 'b']))->toBeFalse();
    });

    it('returns true for zero-indexed sequential array', function () {
        expect(array_is_list([0 => 'a', 1 => 'b', 2 => 'c']))->toBeTrue();
    });
});

describe('is_decimal()', function () {
    it('returns true for decimal values', function () {
        expect(is_decimal(1.5))->toBeTrue();
        expect(is_decimal(10.25))->toBeTrue();
        expect(is_decimal(0.1))->toBeTrue();
    });

    it('returns false for whole numbers', function () {
        expect(is_decimal(1))->toBeFalse();
        expect(is_decimal(10))->toBeFalse();
        expect(is_decimal(0))->toBeFalse();
    });

    it('returns false for string numbers without decimals', function () {
        expect(is_decimal('10'))->toBeFalse();
        expect(is_decimal('100'))->toBeFalse();
    });

    it('returns true for string numbers with decimals', function () {
        expect(is_decimal('10.5'))->toBeTrue();
        expect(is_decimal('0.1'))->toBeTrue();
    });

    it('handles negative numbers', function () {
        expect(is_decimal(-1.5))->toBeTrue();
        expect(is_decimal(-10))->toBeFalse();
    });
});
