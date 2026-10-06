<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $group
 * @property string $key
 * @property string|null $value
 * @property string|null $label
 *
 * @method static \Illuminate\Database\Eloquent\Builder<\VmEngine\Synapse\Models\Config> where($column, $operator = null, $value = null, $boolean = 'and')
 * @method static \VmEngine\Synapse\Models\Config|null first($columns = ['*'])
 * @method static \VmEngine\Synapse\Models\Config updateOrCreate(array<string, mixed> $attributes, array<string, mixed> $values = [])
 * @method static \VmEngine\Synapse\Models\Config findOrFail(int|string $id, array<int, string> $columns = ['*'])
 */
class Config extends Model
{
    protected $fillable = [
        'group',
        'key',
        'value',
        'label',
    ];
}
