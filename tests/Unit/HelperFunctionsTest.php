<?php

use Illuminate\Support\Facades\Route;

describe('synapps_path()', function () {
    it('returns base synapps path when no argument provided', function () {
        $path = synapps_path();
        expect($path)->toContain('synapps');
    });

    it('returns path with provided subdirectory', function () {
        $path = synapps_path('modules/example');
        expect($path)->toContain('synapps')
            ->and($path)->toContain('modules')
            ->and($path)->toContain('example');
    });

    it('handles empty string', function () {
        $path = synapps_path('');
        expect($path)->toContain('synapps');
    });
});

describe('recuresive_array_merge()', function () {
    it('merges simple arrays', function () {
        $array1 = ['a' => 1, 'b' => 2];
        $array2 = ['c' => 3, 'd' => 4];
        $result = recuresive_array_merge($array1, $array2);

        expect($result)->toBe([
            'a' => 1,
            'b' => 2,
            'c' => 3,
            'd' => 4,
        ]);
    });

    it('merges nested arrays', function () {
        $array1 = ['a' => ['x' => 1]];
        $array2 = ['a' => ['y' => 2]];
        $result = recuresive_array_merge($array1, $array2);

        expect($result)->toBe([
            'a' => ['x' => 1, 'y' => 2],
        ]);
    });

    it('overwrites non-array values', function () {
        $array1 = ['a' => 1];
        $array2 = ['a' => 2];
        $result = recuresive_array_merge($array1, $array2);

        expect($result)->toBe(['a' => 2]);
    });

    it('avoids duplicate numeric values', function () {
        $array1 = [1, 2, 3];
        $array2 = [3, 4, 5];
        $result = recuresive_array_merge($array1, $array2);

        expect($result)->toBe([1, 2, 3, 4, 5]);
    });

    it('handles deep nested arrays', function () {
        $array1 = ['a' => ['b' => ['c' => 1]]];
        $array2 = ['a' => ['b' => ['d' => 2]]];
        $result = recuresive_array_merge($array1, $array2);

        expect($result)->toBe([
            'a' => ['b' => ['c' => 1, 'd' => 2]],
        ]);
    });

    it('merges empty arrays', function () {
        $result = recuresive_array_merge([], []);
        expect($result)->toBe([]);
    });
});

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

describe('route_api_opt()', function () {
    it('returns empty prefix and domain when URLs are identical', function () {
        $result = route_api_opt('http://example.com', 'http://example.com');
        expect($result)->toBe(['prefix' => '', 'domain' => '']);
    });

    it('returns prefix when paths differ', function () {
        $result = route_api_opt('http://example.com', 'http://example.com/api');
        expect($result['prefix'])->toBe('/api');
    });

    it('returns domain when hosts differ', function () {
        $result = route_api_opt('http://example.com', 'http://api.example.com');
        expect($result['domain'])->toBe('api.example.com');
    });

    it('handles both prefix and domain differences', function () {
        $result = route_api_opt('http://example.com', 'http://api.example.com/v1');
        expect($result['prefix'])->toBe('/v1')
            ->and($result['domain'])->toBe('api.example.com');
    });
});

describe('route_web_opt()', function () {
    it('returns empty domain when hosts are same', function () {
        $result = route_web_opt('http://example.com', 'http://example.com');
        expect($result)->toBe(['domain' => '']);
    });

    it('returns domain when hosts differ', function () {
        $result = route_web_opt('http://example.com', 'http://app.example.com');
        expect($result['domain'])->toBe('app.example.com');
    });
});

describe('pagination_format()', function () {
    it('formats pagination data correctly', function () {
        $result = pagination_format(100, 0, 10);

        expect($result)->toHaveKey('total')
            ->and($result)->toHaveKey('per_page')
            ->and($result)->toHaveKey('current_page')
            ->and($result)->toHaveKey('from')
            ->and($result)->toHaveKey('to')
            ->and($result['total'])->toBe(100)
            ->and($result['per_page'])->toBe(10);
    });

    it('calculates current page correctly', function () {
        $result = pagination_format(100, 20, 10);
        expect($result['current_page'])->toBe(3);
    });

    it('handles zero limit', function () {
        $result = pagination_format(100, 0, 0);
        expect($result['current_page'])->toBe(1)
            ->and($result['to'])->toBe(1);
    });

    it('handles first page', function () {
        $result = pagination_format(50, 0, 10);
        expect($result['current_page'])->toBe(1);
    });
});

describe('pagination_convert_link()', function () {
    it('converts page parameter to offset and limit', function () {
        $url = 'http://example.com/users?page=3';
        $result = pagination_convert_link($url, 10);

        expect($result)->toContain('offset=20')
            ->and($result)->toContain('limit=10')
            ->and($result)->not->toContain('page=');
    });

    it('preserves other query parameters', function () {
        $url = 'http://example.com/users?page=2&filter=active';
        $result = pagination_convert_link($url, 10);

        expect($result)->toContain('filter=active')
            ->and($result)->toContain('offset=10');
    });

    it('returns unchanged URL when no query string', function () {
        $url = 'http://example.com/users';
        $result = pagination_convert_link($url, 10);

        expect($result)->toBe($url);
    });

    it('handles URLs with port', function () {
        $url = 'http://example.com:8080/users?page=2';
        $result = pagination_convert_link($url, 10);

        expect($result)->toContain(':8080')
            ->and($result)->toContain('offset=10');
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

describe('clean_number_format()', function () {
    it('formats whole numbers without decimals', function () {
        $result = clean_number_format(1500.00);
        expect($result)->toBe('1.500');
    });

    it('formats decimal numbers with precision', function () {
        $result = clean_number_format(1500.02, 2);
        expect($result)->toBe('1.500,02');
    });

    it('handles empty string', function () {
        $result = clean_number_format('');
        expect($result)->toBe('0');
    });

    it('uses custom separators', function () {
        $result = clean_number_format(1500.50, 2, '.', ',');
        expect($result)->toBe('1,500.50');
    });

    it('removes unnecessary decimals for whole numbers', function () {
        $result = clean_number_format(1000, 2);
        expect($result)->toBe('1.000');
    });

    it('formats large numbers', function () {
        $result = clean_number_format(1000000);
        expect($result)->toBe('1.000.000');
    });

    it('handles zero', function () {
        $result = clean_number_format(0);
        expect($result)->toBe('0');
    });
});

describe('template_assets()', function () {
    it('generates correct URL for template assets', function () {
        $result = template_assets('css', 'style.css');
        expect($result)->toContain('/assets/template/default/css/style.css');
    });

    it('handles empty folder and file', function () {
        $result = template_assets();
        expect($result)->toContain('/assets/template/default/');
    });

    it('handles folder with leading/trailing slashes', function () {
        $result = template_assets('/css/', 'style.css');
        expect($result)->toContain('/assets/template/default/css/style.css');
    });

    it('handles file with leading slash', function () {
        $result = template_assets('js', '/script.js');
        expect($result)->toContain('/assets/template/default/js/script.js');
    });
});
