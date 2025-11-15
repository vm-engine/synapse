<?php

declare(strict_types=1);

namespace SynApps\Modules\Todos\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Todo extends Model
{
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \SynDB\Modules\Todos\Factories\TodoFactory::new();
    }

    protected $fillable = [
        'title',
        'detail',
        'tag',
        'due_date',
        'completed',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'tag' => 'array',
            'due_date' => 'datetime',
            'completed' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Scope to get only completed todos
     */
    public function scopeCompleted($query)
    {
        return $query->where('completed', true);
    }

    /**
     * Scope to get only pending todos
     */
    public function scopePending($query)
    {
        return $query->where('completed', false);
    }

    /**
     * Scope to get overdue todos
     */
    public function scopeOverdue($query)
    {
        return $query->where('completed', false)
            ->whereNotNull('due_date')
            ->where('due_date', '<', now());
    }

    /**
     * Scope to search todos
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
                ->orWhere('detail', 'like', "%{$search}%");
        });
    }
}
