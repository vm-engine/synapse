<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use VmEngine\Synapse\Enums\ApiLogActorType;

/**
 * @property int $id
 * @property string $method
 * @property string $url
 * @property int $status_code
 * @property ApiLogActorType $actor_type
 * @property int|null $actor_id
 * @property string|null $actor_name
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property int $duration_ms
 * @property array<string, mixed>|null $request_headers
 * @property array<string, mixed>|null $request_body
 * @property array<string, mixed>|null $response_body
 * @property Carbon|null $created_at
 */
class ApiRequestLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'method',
        'url',
        'status_code',
        'actor_type',
        'actor_id',
        'actor_name',
        'ip_address',
        'user_agent',
        'duration_ms',
        'request_headers',
        'request_body',
        'response_body',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'actor_type' => ApiLogActorType::class,
            'status_code' => 'integer',
            'duration_ms' => 'integer',
            'actor_id' => 'integer',
            'request_headers' => 'array',
            'request_body' => 'array',
            'response_body' => 'array',
        ];
    }
}
