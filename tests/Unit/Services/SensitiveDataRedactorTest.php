<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use VmEngine\Synapse\Services\SensitiveDataRedactor;

describe('SensitiveDataRedactor', function () {
    it('redacts top-level sensitive keys case-insensitively', function () {
        $redactor = new SensitiveDataRedactor(['password', 'authorization']);

        $result = $redactor->redact([
            'Password' => 'secret123',
            'AUTHORIZATION' => 'Bearer abc',
            'email' => 'jane@example.com',
        ]);

        expect($result['Password'])->toBe('[REDACTED:Password]');
        expect($result['AUTHORIZATION'])->toBe('[REDACTED:AUTHORIZATION]');
        expect($result['email'])->toBe('jane@example.com');
    });

    it('redacts nested sensitive keys recursively', function () {
        $redactor = new SensitiveDataRedactor(['token']);

        $result = $redactor->redact([
            'user' => [
                'name' => 'Jane',
                'token' => 'xyz',
            ],
        ]);

        expect($result['user']['token'])->toBe('[REDACTED:token]');
        expect($result['user']['name'])->toBe('Jane');
    });

    it('redacts sensitive keys inside header-style arrays with array values', function () {
        $redactor = new SensitiveDataRedactor(['authorization']);

        $result = $redactor->redact([
            'authorization' => ['Bearer abc'],
            'accept' => ['application/json'],
        ]);

        expect($result['authorization'])->toBe(['[REDACTED:authorization]']);
        expect($result['accept'])->toBe(['application/json']);
    });

    it('replaces uploaded files with a descriptive placeholder', function () {
        $redactor = new SensitiveDataRedactor(['password']);
        $file = UploadedFile::fake()->create('report.pdf', 10);

        $result = $redactor->redact(['attachment' => $file]);

        expect($result['attachment'])->toBe('[FILE:attachment:report.pdf]');
    });

    it('truncates serialized payloads over the max size while staying valid JSON', function () {
        $redactor = new SensitiveDataRedactor([], maxBodySize: 20);

        $result = $redactor->redactAndTruncate(['field' => str_repeat('a', 100)]);

        $decoded = json_decode($result, true);

        expect($decoded)->not->toBeNull();
        expect($decoded['_truncated'])->toBeTrue();
        expect(strlen($decoded['preview']))->toBe(20);
    });

    it('does not truncate payloads within the max size', function () {
        $redactor = new SensitiveDataRedactor([], maxBodySize: 1000);

        $result = $redactor->redactAndTruncate(['field' => 'short']);

        expect($result)->toBe(json_encode(['field' => 'short']));
    });
});
