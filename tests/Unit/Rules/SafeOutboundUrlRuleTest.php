<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Validator;
use VmEngine\Synapse\Rules\SafeOutboundUrlRule;

describe('SafeOutboundUrlRule', function () {
    it('passes for an empty value', function () {
        $validator = Validator::make(['url' => ''], ['url' => [new SafeOutboundUrlRule]]);

        expect($validator->passes())->toBeTrue();
    });

    it('passes for a safe external URL', function () {
        $validator = Validator::make(['url' => 'https://8.8.8.8/'], ['url' => [new SafeOutboundUrlRule]]);

        expect($validator->passes())->toBeTrue();
    });

    it('fails for a loopback URL', function () {
        $validator = Validator::make(['url' => 'http://127.0.0.1/'], ['url' => [new SafeOutboundUrlRule]]);

        expect($validator->fails())->toBeTrue();
    });

    it('fails for a cloud metadata URL', function () {
        $validator = Validator::make(
            ['url' => 'http://169.254.169.254/latest/meta-data/'],
            ['url' => [new SafeOutboundUrlRule]]
        );

        expect($validator->fails())->toBeTrue();
    });

    it('fails for a non-http(s) scheme', function () {
        $validator = Validator::make(['url' => 'file:///etc/passwd'], ['url' => [new SafeOutboundUrlRule]]);

        expect($validator->fails())->toBeTrue();
    });
});
