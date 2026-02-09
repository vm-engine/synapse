<?php

namespace VmEngine\Synapse\Traits;

use Illuminate\Support\Facades\Crypt;

/**
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

        return Crypt::encryptString($id.'|'.$salt);
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
            [$id, $salt] = explode('|', $decrypted);

            if ($salt !== session('dToken')) {
                return false;
            }

            return $id;
        } catch (\Exception $e) {
            return false;
        }
    }
}
