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
     * Whether the given IP address is a public, routable address —
     * i.e. not private (RFC1918), loopback, link-local, or otherwise
     * reserved, for either IPv4 or IPv6.
     */
    protected static function isPublicIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
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
