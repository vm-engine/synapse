<?php

declare(strict_types=1);

use VmEngine\Synapse\Services\ConfigDiff;

describe('changed', function () {
    it('returns nothing when nothing changed', function () {
        $config = ['title' => 'App', 'urls' => ['prefix' => 'web'], 'langs' => ['en', 'id']];

        expect(ConfigDiff::changed($config, $config))->toBe([]);
    });

    it('returns only the changed leaf of a nested map', function () {
        $before = ['title' => 'App', 'urls' => ['prefix' => 'web', 'api_prefix' => 'api']];
        $after = ['title' => 'App', 'urls' => ['prefix' => 'site', 'api_prefix' => 'api']];

        expect(ConfigDiff::changed($after, $before))->toBe(['urls' => ['prefix' => 'site']]);
    });

    it('returns a changed list whole', function () {
        $before = ['security' => ['ip_whitelist' => ['10.0.0.1', '10.0.0.2']]];
        $after = ['security' => ['ip_whitelist' => ['10.0.0.1']]];

        expect(ConfigDiff::changed($after, $before))->toBe(['security' => ['ip_whitelist' => ['10.0.0.1']]]);
    });

    it('returns keys missing from the original', function () {
        expect(ConfigDiff::changed(['a' => 1, 'b' => null], ['a' => 1]))->toBe(['b' => null]);
    });
});

describe('merge', function () {
    it('merges nested maps key by key', function () {
        $base = ['features' => ['totp' => true, 'registration' => true]];
        $override = ['features' => ['totp' => false]];

        expect(ConfigDiff::merge($base, $override))->toBe(['features' => ['totp' => false, 'registration' => true]]);
    });

    it('replaces lists instead of merging them by index', function () {
        expect(ConfigDiff::merge(['ips' => ['a', 'b']], ['ips' => ['c']]))->toBe(['ips' => ['c']]);
    });

    it('lets an empty list clear a list', function () {
        expect(ConfigDiff::merge(['ips' => ['a', 'b']], ['ips' => []]))->toBe(['ips' => []]);
    });

    it('does not wipe a map when the override is an empty object', function () {
        expect(ConfigDiff::merge(['urls' => ['prefix' => 'web']], ['urls' => []]))->toBe(['urls' => ['prefix' => 'web']]);
    });
});

describe('writeDev', function () {
    beforeEach(function () {
        $this->devFile = sys_get_temp_dir().'/config-diff-'.uniqid().'.dev.json';
    });

    afterEach(function () {
        @unlink($this->devFile);
    });

    it('writes only changed keys and keeps existing dev keys', function () {
        file_put_contents($this->devFile, json_encode(['title' => 'Dev title', 'urls' => ['prefix' => 'dev']]));

        $before = ['title' => 'Dev title', 'author' => 'Me', 'urls' => ['prefix' => 'dev', 'api_prefix' => 'api']];
        $after = ['title' => 'Dev title', 'author' => 'Me', 'urls' => ['prefix' => 'dev', 'api_prefix' => 'v2']];

        $changed = ConfigDiff::writeDev($this->devFile, $after, $before);

        expect($changed)->toBe(['urls' => ['api_prefix' => 'v2']])
            ->and(json_decode((string) file_get_contents($this->devFile), true))
            ->toBe(['title' => 'Dev title', 'urls' => ['prefix' => 'dev', 'api_prefix' => 'v2']]);
    });

    it('creates the dev file when it does not exist yet', function () {
        ConfigDiff::writeDev($this->devFile, ['title' => 'New'], ['title' => 'Old']);

        expect(json_decode((string) file_get_contents($this->devFile), true))->toBe(['title' => 'New']);
    });

    it('does not touch the dev file when nothing changed', function () {
        $changed = ConfigDiff::writeDev($this->devFile, ['title' => 'Same'], ['title' => 'Same']);

        expect($changed)->toBe([])
            ->and(file_exists($this->devFile))->toBeFalse();
    });
});
