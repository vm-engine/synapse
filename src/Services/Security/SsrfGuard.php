<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Services\Security;

/**
 * Guards against Server-Side Request Forgery by validating that a URL
 * the application is about to fetch server-side does not point at a
 * private, loopback, link-local, or otherwise reserved address.
 */
class SsrfGuard
{
    /**
     * Whether the given URL is safe for the application to make an
     * outbound HTTP(S) request to. An empty string is considered safe
     * (treated as "not configured", not a security failure).
     */
    public static function isSafeUrl(string $url): bool
    {
        if (trim($url) === '') {
            return true;
        }

        $parts = parse_url($url);
        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            return false;
        }

        $scheme = strtolower($parts['scheme']);
        if (! in_array($scheme, ['http', 'https'], true)) {
            return false;
        }

        $host = $parts['host'];

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return static::isPublicIp($host);
        }

        $ips = static::resolveHost($host);
        if ($ips === []) {
            return false;
        }

        foreach ($ips as $ip) {
            if (! static::isPublicIp($ip)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Whether the given IP address is safe to fetch — a public address,
     * or an RFC1918/ULA private address (common for VPN/leased-line
     * partner endpoints, e.g. bank sandbox/production APIs). Loopback,
     * link-local (including the 169.254.169.254 cloud metadata address),
     * and other non-private reserved ranges are still rejected, for
     * either IPv4 or IPv6 — unless the config flag opts loopback back in
     * outside production (see loopbackAllowed()).
     */
    protected static function isPublicIp(string $ip): bool
    {
        if (static::isLoopback($ip) && static::loopbackAllowed()) {
            return true;
        }

        $isPrivate = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE) === false;
        $isReserved = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE) === false;

        return ! $isReserved || $isPrivate;
    }

    protected static function isLoopback(string $ip): bool
    {
        return $ip === '::1' || str_starts_with($ip, '127.');
    }

    /**
     * ponytail: temporary local-dev escape hatch for pointing a sandbox
     * base_url at 127.0.0.1 without a tunnel — remove once no longer
     * needed. Never honored in production regardless of the config value.
     * Toggle via `security.ssrf_allow_loopback` in synapps/config/apps.json,
     * then run `php artisan synapps:config` — see config('synapps.apps.security').
     */
    protected static function loopbackAllowed(): bool
    {
        return ! app()->environment('production')
            && filter_var(config('synapps.apps.security.ssrf_allow_loopback', false), FILTER_VALIDATE_BOOL);
    }

    /**
     * Resolve a hostname to its IPv4 and IPv6 addresses.
     *
     * @return list<string>
     */
    protected static function resolveHost(string $host): array
    {
        $records = @dns_get_record($host, DNS_A + DNS_AAAA);
        if ($records === false) {
            return [];
        }

        $ips = [];
        foreach ($records as $record) {
            if (isset($record['ip'])) {
                $ips[] = $record['ip'];
            }
            if (isset($record['ipv6'])) {
                $ips[] = $record['ipv6'];
            }
        }

        return $ips;
    }
}
