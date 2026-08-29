<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Services;

use Illuminate\Http\UploadedFile;

class SensitiveDataRedactor
{
    /**
     * @param  array<int, string>  $sensitiveKeys
     */
    public function __construct(
        protected array $sensitiveKeys = [],
        protected int $maxBodySize = 65536,
    ) {}

    /**
     * Recursively redact sensitive keys in an array, replacing matched
     * values with a self-describing "[REDACTED:{key}]" placeholder.
     *
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed>
     */
    public function redact(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            if ($value instanceof UploadedFile) {
                $result[$key] = "[FILE:{$key}:{$value->getClientOriginalName()}]";

                continue;
            }

            if ($this->isSensitiveKey((string) $key)) {
                $result[$key] = $this->redactValue($key, $value);

                continue;
            }

            $result[$key] = is_array($value) ? $this->redact($value) : $value;
        }

        return $result;
    }

    /**
     * Redact the given data and JSON-encode it, truncating if the encoded
     * payload exceeds the configured max body size. Always returns valid
     * JSON so callers can safely `json_decode()` the result.
     *
     * @param  array<array-key, mixed>  $data
     */
    public function redactAndTruncate(array $data): string
    {
        $encoded = json_encode($this->redact($data));

        if ($encoded === false) {
            return '{"_error":"unserializable"}';
        }

        if (strlen($encoded) <= $this->maxBodySize) {
            return $encoded;
        }

        return json_encode([
            '_truncated' => true,
            'original_size' => strlen($encoded),
            'preview' => substr($encoded, 0, $this->maxBodySize),
        ]) ?: '{"_error":"unserializable"}';
    }

    protected function isSensitiveKey(string $key): bool
    {
        foreach ($this->sensitiveKeys as $sensitiveKey) {
            if (strcasecmp($key, $sensitiveKey) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Replace a sensitive value, preserving array shape (e.g. single-value
     * header arrays like `['Bearer abc']`) while still describing the key.
     */
    protected function redactValue(int|string $key, mixed $value): mixed
    {
        if (is_array($value)) {
            return array_fill(0, count($value), "[REDACTED:{$key}]");
        }

        return "[REDACTED:{$key}]";
    }
}
