<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use VmEngine\Synapse\Traits\WithDeleteToken;

class DeleteTokenAlpha extends Model
{
    use WithDeleteToken;
}

class DeleteTokenBeta extends Model
{
    use WithDeleteToken;
}

class DeleteTokenAlphaChild extends DeleteTokenAlpha {}

describe('WithDeleteToken', function () {
    it('validates a token on the model that issued it', function () {
        $token = (new DeleteTokenAlpha)->generateDeleteToken(5);

        expect(DeleteTokenAlpha::validateDeleteToken($token))->toBe('5');
    });

    it('refuses a token issued by another model', function () {
        $token = (new DeleteTokenAlpha)->generateDeleteToken(5);

        expect(DeleteTokenBeta::validateDeleteToken($token))->toBeFalse();
    });

    it('accepts a subclass token on its parent model', function () {
        $token = (new DeleteTokenAlphaChild)->generateDeleteToken(5);

        expect(DeleteTokenAlpha::validateDeleteToken($token))->toBe('5');
    });

    it('refuses the old class-less token format', function () {
        (new DeleteTokenAlpha)->generateDeleteToken(5);

        expect(DeleteTokenAlpha::validateDeleteToken(Crypt::encryptString('5|'.session('dToken'))))->toBeFalse();
    });

    it('refuses garbage, empty tokens and tokens without a session salt', function () {
        $token = (new DeleteTokenAlpha)->generateDeleteToken(5);

        expect(DeleteTokenAlpha::validateDeleteToken('not-a-token'))->toBeFalse()
            ->and(DeleteTokenAlpha::validateDeleteToken(''))->toBeFalse();

        session()->forget('dToken');

        expect(DeleteTokenAlpha::validateDeleteToken($token))->toBeFalse();
    });
});
