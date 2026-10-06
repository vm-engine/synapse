<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use VmEngine\Synapse\Enums\ExcelTaskStatus;

/**
 * @property int $id
 * @property ExcelTaskStatus $status
 * @property string|null $output_path
 * @property int|null $user_id
 * @property string|null $reference
 * @property string|null $query_class
 * @property int|null $total_rows
 * @property int $processed_rows
 * @property array<string, mixed>|null $options
 * @property string|null $error_message
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $last_heartbeat_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<\VmEngine\Synapse\Models\ExcelExport> where($column, $operator = null, $value = null, $boolean = 'and')
 * @method static \VmEngine\Synapse\Models\ExcelExport create(array<string, mixed> $attributes)
 * @method static \VmEngine\Synapse\Models\ExcelExport|null find(int|string $id, array<int, string> $columns = ['*'])
 * @method static \VmEngine\Synapse\Models\ExcelExport findOrFail(int|string $id, array<int, string> $columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Builder<\VmEngine\Synapse\Models\ExcelExport> forReference(string $reference)
 */
class ExcelExport extends Model
{
    protected $fillable = [
        'status',
        'output_path',
        'user_id',
        'reference',
        'query_class',
        'total_rows',
        'processed_rows',
        'options',
        'error_message',
        'started_at',
        'completed_at',
        'last_heartbeat_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ExcelTaskStatus::class,
            'options' => 'array',
            'user_id' => 'integer',
            'total_rows' => 'integer',
            'processed_rows' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'last_heartbeat_at' => 'datetime',
        ];
    }

    /**
     * Find an export task by its reference string.
     */
    public static function findByReference(string $reference): ?self
    {
        return static::where('reference', $reference)->latest('id')->first();
    }

    /**
     * Scope a query to tasks with the given reference.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForReference(Builder $query, string $reference): Builder
    {
        return $query->where('reference', $reference);
    }

    /**
     * Check if the task is considered stale (no heartbeat within timeout).
     */
    public function isStale(int $timeoutSeconds = 3900): bool
    {
        if ($this->status !== ExcelTaskStatus::Processing) {
            return false;
        }

        if ($this->last_heartbeat_at === null) {
            return $this->started_at !== null
                && $this->started_at->diffInSeconds(now()) > $timeoutSeconds;
        }

        return $this->last_heartbeat_at->diffInSeconds(now()) > $timeoutSeconds;
    }

    /**
     * Mark the task as failed.
     */
    public function markFailed(string $errorMessage): void
    {
        $this->update([
            'status' => ExcelTaskStatus::Failed,
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Mark the task as completed.
     */
    public function markCompleted(): void
    {
        $this->update([
            'status' => ExcelTaskStatus::Completed,
            'completed_at' => now(),
        ]);
    }

    /**
     * Update heartbeat timestamp.
     */
    public function heartbeat(): void
    {
        $this->update(['last_heartbeat_at' => now()]);
    }
}
