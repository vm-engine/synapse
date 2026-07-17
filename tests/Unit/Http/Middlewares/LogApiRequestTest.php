<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use VmEngine\Synapse\Enums\ApiLogActorType;
use VmEngine\Synapse\Models\ApiRequestLog;

uses(RefreshDatabase::class);

describe('LogApiRequest middleware', function () {
    beforeEach(function () {
        Route::post('/__test/api-log', function () {
            return response()->json(['id' => 1, 'password' => 'should-not-leak-here-either']);
        })->middleware(['api', 'api.log']);
    });

    it('logs a request and redacts sensitive request fields', function () {
        $response = $this->postJson('/__test/api-log', [
            'email' => 'jane@example.com',
            'password' => 'super-secret',
        ], [
            'Authorization' => 'Bearer some-token',
        ]);

        $response->assertOk();

        $log = ApiRequestLog::sole();

        expect($log->method)->toBe('POST');
        expect($log->url)->toContain('/__test/api-log');
        expect($log->status_code)->toBe(200);
        expect($log->actor_type)->toBe(ApiLogActorType::Guest);
        expect($log->duration_ms)->toBeGreaterThanOrEqual(0);
        expect($log->request_body['email'])->toBe('jane@example.com');
        expect($log->request_body['password'])->toBe('[REDACTED:password]');
        expect($log->request_headers['authorization'])->toBe(['[REDACTED:authorization]']);
    });

    it('redacts the cookie header so session data never lands in the log', function () {
        $this->postJson('/__test/api-log', ['email' => 'jane@example.com'], [
            'Cookie' => 'laravel_session=abc123',
        ]);

        $log = ApiRequestLog::sole();

        expect($log->request_headers['cookie'])->toBe(['[REDACTED:cookie]']);
    });

    it('redacts sensitive fields in the response body', function () {
        $this->postJson('/__test/api-log', ['email' => 'jane@example.com']);

        $log = ApiRequestLog::sole();

        expect($log->response_body['id'])->toBe(1);
        expect($log->response_body['password'])->toBe('[REDACTED:password]');
    });

    it('does not break the response when logging is applied', function () {
        $response = $this->postJson('/__test/api-log', ['email' => 'jane@example.com']);

        $response->assertOk()->assertJson(['id' => 1]);
    });
});
