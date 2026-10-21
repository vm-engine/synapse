<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Traits;

use Illuminate\Support\Facades\Crypt;

/**
 * Encrypted, session-bound delete tokens. A token carries the class that issued it, so it only
 * validates on that model (or a parent class it extends), never on an unrelated model with the same id.
 *
 * @phpstan-ignore trait.unused
 */
trait WithDeleteToken
{
    protected static function bootWithDeleteToken(): void
    {
        static::retrieved(function ($model) {
            $model->append('delete_token');
        });
    }

    protected function initializeWithDeleteToken(): void
    {
        $this->mergeHidden(['delete_token']);
    }

    public function getDeleteTokenAttribute(): string
    {
        return $this->generateDeleteToken($this->getKey());
    }

    public function generateDeleteToken($id): string
    {
        if (! session('dToken')) {
            $salt = bin2hex(random_bytes(16));
            session(['dToken' => $salt]);
        } else {
            $salt = session('dToken');
        }

        return Crypt::encryptString(static::class.'|'.$id.'|'.$salt);
    }

    public static function validateDeleteToken($token): string|false
    {
        if (! $token) {
            return false;
        }

        if (! session('dToken')) {
            return false;
        }

        try {
            $decrypted = Crypt::decryptString($token);
            $parts = explode('|', $decrypted, 3);

            if (count($parts) !== 3) {
                return false;
            }

            [$class, $id, $salt] = $parts;

            if ($salt !== session('dToken') || ! is_a($class, static::class, true)) {
                return false;
            }

            return $id;
        } catch (\Exception $e) {
            return false;
        }
    }
}
