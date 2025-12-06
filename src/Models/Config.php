<?php

namespace VmEngine\Synapse\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $group
 * @property string $key
 * @property string|null $value
 * @property string|null $label
 *
 * @method static \Illuminate\Database\Eloquent\Builder where($column, $operator = null, $value = null, $boolean = 'and')
 * @method static \VmEngine\Synapse\Models\Config|null first($columns = ['*'])
 * @method static \VmEngine\Synapse\Models\Config updateOrCreate(array $attributes, array $values = [])
 * @method static \VmEngine\Synapse\Models\Config findOrFail(int|string $id, array $columns = ['*'])
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
