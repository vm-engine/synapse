<?php

declare(strict_types=1);

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use VmEngine\Synapse\Http\Middlewares\IpFilter;

/**
 * @param  array<int, string>  $whitelist
 * @param  array<int, string>  $blacklist
 */
function runIpFilter(string $ip, array $whitelist = [], array $blacklist = []): int
{
    config([
        'synapps.apps.security.ip_whitelist' => $whitelist,
        'synapps.apps.security.ip_blacklist' => $blacklist,
    ]);

    $request = Request::create('/', 'GET', server: ['REMOTE_ADDR' => $ip]);

    try {
        return (new IpFilter)->handle($request, fn () => new Response('ok'))->getStatusCode();
    } catch (HttpException $e) {
        return $e->getStatusCode();
    }
}

test('allows everyone when both lists are empty', function () {
    expect(runIpFilter('203.0.113.5'))->toBe(200);
});

test('blocks a blacklisted ip', function () {
    expect(runIpFilter('203.0.113.5', blacklist: ['203.0.113.5']))->toBe(403)
        ->and(runIpFilter('203.0.113.6', blacklist: ['203.0.113.5']))->toBe(200);
});

test('only allows whitelisted ips when the whitelist is set', function () {
    expect(runIpFilter('10.0.0.1', whitelist: ['10.0.0.1']))->toBe(200)
        ->and(runIpFilter('10.0.0.2', whitelist: ['10.0.0.1']))->toBe(403);
});

test('matches cidr ranges and ipv6', function () {
    expect(runIpFilter('10.1.2.3', whitelist: ['10.0.0.0/8']))->toBe(200)
        ->and(runIpFilter('11.0.0.1', whitelist: ['10.0.0.0/8']))->toBe(403)
        ->and(runIpFilter('2001:db8::1', blacklist: ['2001:db8::/32']))->toBe(403);
});

test('blacklist wins when an ip is in both lists', function () {
    expect(runIpFilter('10.0.0.1', whitelist: ['10.0.0.0/8'], blacklist: ['10.0.0.1']))->toBe(403);
});

test('parseList trims, drops blanks and de-duplicates', function () {
    expect(IpFilter::parseList(' 10.0.0.1, ,10.0.0.0/8,10.0.0.1 '))->toBe(['10.0.0.1', '10.0.0.0/8'])
        ->and(IpFilter::parseList(''))->toBe([]);
});

test('isValidEntry accepts ips and cidr, rejects garbage', function (string $entry, bool $valid) {
    expect(IpFilter::isValidEntry($entry))->toBe($valid);
})->with([
    ['10.0.0.1', true],
    ['10.0.0.0/8', true],
    ['2001:db8::/32', true],
    ['10.0.0.0/33', false],
    ['2001:db8::/129', false],
    ['10.0.0.0/', false],
    ['999.1.1.1', false],
    ['example.com', false],
]);

test('is registered as global middleware', function () {
    expect(app(Kernel::class)->hasMiddleware(IpFilter::class))->toBeTrue();
});
