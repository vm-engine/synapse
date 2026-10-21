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

    it('issues the same url-safe token for a record within one session', function () {
        $first = (new DeleteTokenAlpha)->generateDeleteToken(5);

        expect((new DeleteTokenAlpha)->generateDeleteToken(5))->toBe($first)
            ->and($first)->toMatch('/^[A-Za-z0-9_-]+$/')
            ->and((new DeleteTokenAlpha)->generateDeleteToken(6))->not->toBe($first);

        session()->forget('dToken');

        expect((new DeleteTokenAlpha)->generateDeleteToken(5))->not->toBe($first);
    });

    it('refuses a tampered token', function () {
        $token = (new DeleteTokenAlpha)->generateDeleteToken(5);
        $tampered = substr($token, 0, 10).($token[10] === 'A' ? 'B' : 'A').substr($token, 11);

        expect(DeleteTokenAlpha::validateDeleteToken($tampered))->toBeFalse();
    });

    it('refuses tokens in the older Crypt formats', function () {
        (new DeleteTokenAlpha)->generateDeleteToken(5);
        $salt = session('dToken');

        expect(DeleteTokenAlpha::validateDeleteToken(Crypt::encryptString('5|'.$salt)))->toBeFalse()
            ->and(DeleteTokenAlpha::validateDeleteToken(Crypt::encryptString(DeleteTokenAlpha::class.'|5|'.$salt)))->toBeFalse();
    });

    it('refuses garbage, empty tokens and tokens without a session salt', function () {
        $token = (new DeleteTokenAlpha)->generateDeleteToken(5);

        expect(DeleteTokenAlpha::validateDeleteToken('not-a-token'))->toBeFalse()
            ->and(DeleteTokenAlpha::validateDeleteToken(''))->toBeFalse();

        session()->forget('dToken');

        expect(DeleteTokenAlpha::validateDeleteToken($token))->toBeFalse();
    });

    it('validates many tokens at once, all or nothing', function () {
        $model = new DeleteTokenAlpha;
        [$one, $two] = [$model->generateDeleteToken(1), $model->generateDeleteToken(2)];

        expect(DeleteTokenAlpha::validateDeleteTokens([$one, $two, $one]))->toBe(['1', '2'])
            ->and(DeleteTokenAlpha::validateDeleteTokens([]))->toBe([])
            ->and(DeleteTokenAlpha::validateDeleteTokens([$one, 'not-a-token']))->toBeFalse()
            ->and(DeleteTokenAlpha::validateDeleteTokens([$one, 2]))->toBeFalse()
            ->and(DeleteTokenBeta::validateDeleteTokens([$one]))->toBeFalse();
    });
});
