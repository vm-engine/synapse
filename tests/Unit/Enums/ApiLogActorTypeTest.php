<?php

declare(strict_types=1);

use VmEngine\Synapse\Enums\ApiLogActorType;

describe('ApiLogActorType', function () {
    it('has the three expected cases with string values', function () {
        expect(ApiLogActorType::Guest->value)->toBe('guest');
        expect(ApiLogActorType::User->value)->toBe('user');
        expect(ApiLogActorType::ApiUser->value)->toBe('api_user');
    });
});
