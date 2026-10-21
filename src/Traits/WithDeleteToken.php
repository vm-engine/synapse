<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Traits;

use RuntimeException;

/**
 * Encrypted, session-bound delete tokens. A token carries the class that issued it, so it only
 * validates on that model (or a parent class it extends), never on an unrelated model with the same id.
 *
 * Tokens are stable: the same record gets the same token for the whole session (the IV is derived
 * from the payload), so they can be used as checkbox values for bulk selection.
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

        return self::encodeDeleteToken(static::class.'|'.$id.'|'.$salt);
    }

    public static function validateDeleteToken($token): string|false
    {
        if (! is_string($token) || $token === '' || ! session('dToken')) {
            return false;
        }

        $payload = self::decodeDeleteToken($token);

        if ($payload === null) {
            return false;
        }

        $parts = explode('|', $payload, 3);

        if (count($parts) !== 3) {
            return false;
        }

        [$class, $id, $salt] = $parts;

        if ($salt !== session('dToken') || ! is_a($class, static::class, true)) {
            return false;
        }

        return $id;
    }

    /**
     * Validate a bulk selection: the ids of every token (deduplicated, in order), or false when
     * any one of them is invalid — a bulk delete never runs on a partly forged selection.
     *
     * @param  array<array-key, mixed>  $tokens
     * @return list<string>|false
     */
    public static function validateDeleteTokens(array $tokens): array|false
    {
        $ids = [];

        foreach ($tokens as $token) {
            $id = static::validateDeleteToken($token);

            if ($id === false) {
                return false;
            }

            $ids[] = $id;
        }

        return array_values(array_unique($ids));
    }

    /**
     * AES-256-CBC with an IV derived from the payload (so equal payloads give equal tokens) and an
     * HMAC-SHA256 over IV + ciphertext, encoded as url-safe base64.
     */
    private static function encodeDeleteToken(string $payload): string
    {
        [$encryptionKey, $macKey] = self::deleteTokenKeys();
        $iv = substr(hash_hmac('sha256', $payload, $macKey, true), 0, 16);
        $cipher = (string) openssl_encrypt($payload, 'aes-256-cbc', $encryptionKey, OPENSSL_RAW_DATA, $iv);
        $mac = hash_hmac('sha256', $iv.$cipher, $macKey, true);

        return rtrim(strtr(base64_encode($iv.$cipher.$mac), '+/', '-_'), '=');
    }

    private static function decodeDeleteToken(string $token): ?string
    {
        $raw = base64_decode(str_pad(strtr($token, '-_', '+/'), (int) ceil(strlen($token) / 4) * 4, '='), true);

        // 16-byte IV + at least one 16-byte cipher block + 32-byte MAC.
        if ($raw === false || strlen($raw) < 64) {
            return null;
        }

        [$encryptionKey, $macKey] = self::deleteTokenKeys();
        $iv = substr($raw, 0, 16);
        $cipher = substr($raw, 16, -32);

        if (! hash_equals(hash_hmac('sha256', $iv.$cipher, $macKey, true), substr($raw, -32))) {
            return null;
        }

        $payload = openssl_decrypt($cipher, 'aes-256-cbc', $encryptionKey, OPENSSL_RAW_DATA, $iv);

        return $payload === false ? null : $payload;
    }

    /**
     * Separate encryption and MAC keys derived from the application key.
     *
     * @return array{0: string, 1: string}
     */
    private static function deleteTokenKeys(): array
    {
        $appKey = (string) config('app.key');

        if (str_starts_with($appKey, 'base64:')) {
            $appKey = (string) base64_decode(substr($appKey, 7), true);
        }

        if ($appKey === '') {
            throw new RuntimeException('WithDeleteToken needs an application key (APP_KEY).');
        }

        return [
            hash_hmac('sha256', 'synapse.delete-token.encrypt', $appKey, true),
            hash_hmac('sha256', 'synapse.delete-token.mac', $appKey, true),
        ];
    }
}
