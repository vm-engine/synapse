<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Http\Middlewares;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use VmEngine\Synapse\Enums\ApiLogActorType;
use VmEngine\Synapse\Models\ApiRequestLog;
use VmEngine\Synapse\Services\SensitiveDataRedactor;

class LogApiRequest
{
    /**
     * @var array<int, string>
     */
    protected const DEFAULT_SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'token',
        'access_token',
        'refresh_token',
        'authorization',
        'secret',
        'api_key',
        'card_number',
        'cookie',
        'set-cookie',
        'x-xsrf-token',
    ];

    protected const DEFAULT_MAX_BODY_SIZE = 65536;

    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);

        $response = $next($request);

        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

        $this->log($request, $response, $durationMs);

        return $response;
    }

    protected function log(Request $request, Response $response, int $durationMs): void
    {
        try {
            $redactor = new SensitiveDataRedactor(
                config('synapps.apps.logging.sensitive_keys', self::DEFAULT_SENSITIVE_KEYS),
                config('synapps.apps.logging.max_body_size', self::DEFAULT_MAX_BODY_SIZE),
            );

            $user = $request->user();

            ApiRequestLog::create([
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'status_code' => $response->getStatusCode(),
                'actor_type' => $this->resolveActorType($user),
                'actor_id' => $user?->getAuthIdentifier(),
                'actor_name' => $this->resolveActorName($user),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'duration_ms' => $durationMs,
                'request_headers' => json_decode($redactor->redactAndTruncate($request->headers->all()), true),
                'request_body' => json_decode($redactor->redactAndTruncate($request->all()), true),
                'response_body' => $this->extractResponseBody($response, $redactor),
            ]);
        } catch (\Throwable $exception) {
            Log::error('Failed to log API request', [
                'exception' => $exception->getMessage(),
                'url' => $request->fullUrl(),
            ]);
        }
    }

    protected function resolveActorType(mixed $user): ApiLogActorType
    {
        if ($user === null) {
            return ApiLogActorType::Guest;
        }

        if ($user->is_api_user ?? false) {
            return ApiLogActorType::ApiUser;
        }

        return ApiLogActorType::User;
    }

    protected function resolveActorName(mixed $user): ?string
    {
        if ($user === null) {
            return null;
        }

        return $user->name ?? null;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function extractResponseBody(Response $response, SensitiveDataRedactor $redactor): ?array
    {
        if (! $response instanceof JsonResponse) {
            return null;
        }

        $decoded = json_decode($response->getContent(), true);

        if (! is_array($decoded)) {
            return null;
        }

        return json_decode($redactor->redactAndTruncate($decoded), true);
    }
}
