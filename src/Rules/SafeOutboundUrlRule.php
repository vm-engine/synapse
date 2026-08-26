<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use VmEngine\Synapse\Services\Security\SsrfGuard;

/**
 * Validates that a URL is safe for the application to later fetch
 * server-side — rejects non-http(s) schemes and any host that resolves
 * to a private, loopback, link-local, or reserved address.
 */
class SafeOutboundUrlRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        if (! SsrfGuard::isSafeUrl($value)) {
            $fail('The :attribute must be a valid external http(s) URL and cannot point to a private, loopback, or link-local address.');
        }
    }
}
