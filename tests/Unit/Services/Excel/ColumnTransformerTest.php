<?php

declare(strict_types=1);

use VmEngine\Synapse\Services\Excel\Transformers\BooleanTransformer;
use VmEngine\Synapse\Services\Excel\Transformers\DateFormatTransformer;
use VmEngine\Synapse\Services\Excel\Transformers\DefaultValueTransformer;
use VmEngine\Synapse\Services\Excel\Transformers\NumberFormatTransformer;
use VmEngine\Synapse\Services\Excel\Transformers\UpperCaseTransformer;

describe('DateFormatTransformer', function () {
    it('formats a DateTime object with default format', function () {
        $transformer = new DateFormatTransformer;
        $date = new \DateTimeImmutable('2025-06-15 14:30:00');

        expect($transformer->transform($date, null))->toBe('2025-06-15');
    });

    it('formats a DateTime object with custom format', function () {
        $transformer = new DateFormatTransformer(format: 'd/m/Y');
        $date = new \DateTimeImmutable('2025-06-15 14:30:00');

        expect($transformer->transform($date, null))->toBe('15/06/2025');
    });

    it('formats a date string with datetime format', function () {
        $transformer = new DateFormatTransformer(format: 'Y-m-d H:i');
        $value = '2025-06-15 14:30:00';

        expect($transformer->transform($value, null))->toBe('2025-06-15 14:30');
    });

    it('formats a date-only string', function () {
        $transformer = new DateFormatTransformer(format: 'd M Y');

        expect($transformer->transform('2025-06-15', null))->toBe('15 Jun 2025');
    });

    it('returns null for null value', function () {
        $transformer = new DateFormatTransformer;

        expect($transformer->transform(null, null))->toBeNull();
    });

    it('returns non-parseable strings as-is', function () {
        $transformer = new DateFormatTransformer;

        expect($transformer->transform('not-a-date', null))->toBe('not-a-date');
    });

    it('returns non-string non-date values as-is', function () {
        $transformer = new DateFormatTransformer;

        expect($transformer->transform(12345, null))->toBe(12345);
    });
});

describe('NumberFormatTransformer', function () {
    it('formats an integer with defaults', function () {
        $transformer = new NumberFormatTransformer;

        expect($transformer->transform(1234567, null))->toBe('1,234,567.00');
    });

    it('formats a float with custom decimals', function () {
        $transformer = new NumberFormatTransformer(decimals: 3);

        expect($transformer->transform(1234.5678, null))->toBe('1,234.568');
    });

    it('formats with custom separators', function () {
        $transformer = new NumberFormatTransformer(
            decimals: 2,
            decimalSeparator: ',',
            thousandsSeparator: '.',
        );

        expect($transformer->transform(1234567.89, null))->toBe('1.234.567,89');
    });

    it('formats a numeric string', function () {
        $transformer = new NumberFormatTransformer;

        expect($transformer->transform('9876.5', null))->toBe('9,876.50');
    });

    it('returns null for null value', function () {
        $transformer = new NumberFormatTransformer;

        expect($transformer->transform(null, null))->toBeNull();
    });

    it('returns non-numeric values as-is', function () {
        $transformer = new NumberFormatTransformer;

        expect($transformer->transform('abc', null))->toBe('abc');
    });
});

describe('BooleanTransformer', function () {
    it('transforms true to Yes by default', function () {
        $transformer = new BooleanTransformer;

        expect($transformer->transform(true, null))->toBe('Yes');
    });

    it('transforms false to No by default', function () {
        $transformer = new BooleanTransformer;

        expect($transformer->transform(false, null))->toBe('No');
    });

    it('transforms truthy value to Yes', function () {
        $transformer = new BooleanTransformer;

        expect($transformer->transform(1, null))->toBe('Yes');
    });

    it('transforms falsy value to No', function () {
        $transformer = new BooleanTransformer;

        expect($transformer->transform(0, null))->toBe('No');
    });

    it('uses custom labels', function () {
        $transformer = new BooleanTransformer(trueLabel: 'Active', falseLabel: 'Inactive');

        expect($transformer->transform(true, null))->toBe('Active');
        expect($transformer->transform(false, null))->toBe('Inactive');
    });

    it('returns null for null value', function () {
        $transformer = new BooleanTransformer;

        expect($transformer->transform(null, null))->toBeNull();
    });
});

describe('DefaultValueTransformer', function () {
    it('replaces null with default', function () {
        $transformer = new DefaultValueTransformer;

        expect($transformer->transform(null, null))->toBe('-');
    });

    it('replaces empty string with default', function () {
        $transformer = new DefaultValueTransformer;

        expect($transformer->transform('', null))->toBe('-');
    });

    it('uses custom default value', function () {
        $transformer = new DefaultValueTransformer(default: 'N/A');

        expect($transformer->transform(null, null))->toBe('N/A');
    });

    it('passes through non-empty values', function () {
        $transformer = new DefaultValueTransformer;

        expect($transformer->transform('hello', null))->toBe('hello');
        expect($transformer->transform(0, null))->toBe(0);
        expect($transformer->transform(false, null))->toBe(false);
    });
});

describe('UpperCaseTransformer', function () {
    it('uppercases a string', function () {
        $transformer = new UpperCaseTransformer;

        expect($transformer->transform('hello world', null))->toBe('HELLO WORLD');
    });

    it('returns null for null value', function () {
        $transformer = new UpperCaseTransformer;

        expect($transformer->transform(null, null))->toBeNull();
    });

    it('returns non-string values as-is', function () {
        $transformer = new UpperCaseTransformer;

        expect($transformer->transform(123, null))->toBe(123);
    });
});
