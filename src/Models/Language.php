<?php

namespace VmEngine\Synapse\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $language_code
 * @property string $key
 * @property string $value
 * @property string|null $label
 *
 * @method static \Illuminate\Database\Eloquent\Collection<int, \VmEngine\Synapse\Models\Language> all($columns = ['*'])
 * @method static \VmEngine\Synapse\Models\Language updateOrCreate(array $attributes, array $values = [])
 * @method static \Illuminate\Database\Eloquent\Builder where($column, $operator = null, $value = null, $boolean = 'and')
 * @method static \VmEngine\Synapse\Models\Language findOrFail(int|string $id, array $columns = ['*'])
 */
class Language extends Model
{
    protected $fillable = [
        'language_code',
        'key',
        'value',
        'label',
    ];
}
