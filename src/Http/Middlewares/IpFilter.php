<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Http\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks requests by client IP using `security.ip_whitelist` / `security.ip_blacklist`
 * in synapps/config/apps.json. Both empty = allow all; blacklist wins over whitelist.
 * Relies on $request->ip(), so proxied setups need TrustProxies configured.
 */
class IpFilter
{
    public function handle(Request $request, Closure $next): Response
    {
        $whitelist = (array) config('synapps.apps.security.ip_whitelist', []);
        $blacklist = (array) config('synapps.apps.security.ip_blacklist', []);

        if ($whitelist !== [] || $blacklist !== []) {
            $ip = (string) $request->ip();

            if ($blacklist !== [] && IpUtils::checkIp($ip, $blacklist)) {
                abort(403);
            }

            if ($whitelist !== [] && ! IpUtils::checkIp($ip, $whitelist)) {
                abort(403);
            }
        }

        return $next($request);
    }

    /**
     * Parse comma-separated prompt input into a clean list of IP/CIDR entries.
     *
     * @return array<int, string>
     */
    public static function parseList(string $input): array
    {
        return array_values(array_unique(array_filter(array_map('trim', explode(',', $input)))));
    }

    /**
     * Whether the entry is a valid IPv4/IPv6 address or CIDR range.
     */
    public static function isValidEntry(string $entry): bool
    {
        $parts = explode('/', $entry, 2);

        if (filter_var($parts[0], FILTER_VALIDATE_IP) === false) {
            return false;
        }

        if (! isset($parts[1])) {
            return true;
        }

        $maxMask = str_contains($parts[0], ':') ? 128 : 32;

        return ctype_digit($parts[1]) && (int) $parts[1] <= $maxMask;
    }
}
