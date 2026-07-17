<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use VmEngine\Synapse\Enums\ApiLogActorType;
use VmEngine\Synapse\Models\ApiRequestLog;

uses(RefreshDatabase::class);

describe('ApiRequestLog', function () {
    it('creates a log row with json-cast headers and bodies', function () {
        $log = ApiRequestLog::create([
            'method' => 'POST',
            'url' => '/api/v1/submissions?foo=bar',
            'status_code' => 201,
            'actor_type' => ApiLogActorType::User,
            'actor_id' => 42,
            'actor_name' => 'Jane Doe',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PestTestAgent/1.0',
            'duration_ms' => 12,
            'request_headers' => ['authorization' => '[REDACTED:authorization]'],
            'request_body' => ['password' => '[REDACTED:password]', 'email' => 'jane@example.com'],
            'response_body' => ['id' => 1, 'status' => 'ok'],
        ]);

        $log->refresh();

        expect($log->id)->toBeGreaterThan(0);
        expect($log->actor_type)->toBe(ApiLogActorType::User);
        expect($log->request_headers)->toBeArray();
        expect($log->request_headers['authorization'])->toBe('[REDACTED:authorization]');
        expect($log->request_body['email'])->toBe('jane@example.com');
        expect($log->response_body['status'])->toBe('ok');
        expect($log->duration_ms)->toBe(12);
    });

    it('does not have an updated_at column', function () {
        $log = ApiRequestLog::create([
            'method' => 'GET',
            'url' => '/api/v1/me',
            'status_code' => 200,
            'actor_type' => ApiLogActorType::Guest,
            'duration_ms' => 5,
        ]);

        expect($log->getAttributes())->not->toHaveKey('updated_at');
    });
});
